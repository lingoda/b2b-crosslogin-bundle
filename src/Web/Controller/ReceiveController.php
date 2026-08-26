<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Web\Controller;

use Lingoda\CrossLoginBundle\Event\CrossLoginError;
use Lingoda\CrossLoginBundle\Event\CrossLoginFailedEvent;
use Lingoda\CrossLoginBundle\Event\CrossLoginSucceededEvent;
use Lingoda\CrossLoginBundle\Event\CrossLoginTokenReceivedEvent;
use Lingoda\CrossLoginBundle\Routing\CrossLoginRoutes;
use Lingoda\CrossLoginBundle\Web\Receiver;
use Lingoda\CrossLoginBundle\Web\Receivers;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Lands a cross-login hand-off and lets the receiving app decide what a session is.
 *
 * Only stateless receivers need this. An app whose firewall is stateful already gets a session
 * from the JWT on any URL and has nothing to land on. A stateless app has to exchange the
 * short-lived token for its own durable credentials, and that exchange is app-specific — so the
 * bundle owns the route, the claim decoding and the failure shape, and dispatches events for
 * the rest.
 *
 * The controller deliberately re-parses the raw token instead of trusting the firewall alone:
 * BypassFailureJWTAuthenticator lets an unresolvable token through rather than returning 401,
 * so the claims are needed to provision a user the app has never seen. Parsing goes through the
 * receiver's JWT manager, which dispatches lexik's JWT_DECODED — so JWTDecodedListener still
 * enforces the audience.
 */
final readonly class ReceiveController
{
    public function __construct(
        private Receivers $receivers,
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $dispatcher,
        private UrlGeneratorInterface $urlGenerator,
        private string $tokenParamName,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $name = $request->attributes->get(CrossLoginRoutes::RECEIVER_ATTRIBUTE);
        Assert::stringNotEmpty($name, \sprintf(
            'The receive route must carry a "%s" default naming the configured receiver.',
            CrossLoginRoutes::RECEIVER_ATTRIBUTE,
        ));

        $receiver = $this->receivers->get($name);

        // The claims are decoded for provisioning, not for authentication — the firewall already
        // decided that. A user can arrive with no token at all and still be authenticated, because
        // lexik's cookie extractor accepts a session the app minted on an earlier hand-off. So a
        // missing or unusable token is only fatal when nothing else identifies the visitor.
        $payload = [];
        $tokenError = null;
        $tokenException = null;

        $rawToken = $request->query->get($this->tokenParamName);
        if (!\is_string($rawToken) || $rawToken === '') {
            $tokenError = CrossLoginError::MissingToken;
        } else {
            try {
                /** @var array<string, mixed> $payload */
                $payload = $receiver->jwtManager->parse($rawToken);
            } catch (\Throwable $e) {
                $tokenError = CrossLoginError::InvalidToken;
                $tokenException = $e;
            }
        }

        $received = new CrossLoginTokenReceivedEvent(
            $receiver->name,
            $payload,
            $request,
            $this->currentUser(),
        );
        $this->dispatcher->dispatch($received, $this->eventName($receiver, 'token_received'));

        $user = $received->getUser();
        if (!$user instanceof UserInterface) {
            return $this->fail(
                $receiver,
                $tokenError ?? CrossLoginError::UnknownUser,
                $request,
                $tokenException,
            );
        }

        $succeeded = new CrossLoginSucceededEvent($receiver->name, $user, $payload, $request);
        $this->dispatcher->dispatch($succeeded, $this->eventName($receiver, 'succeeded'));

        return $succeeded->getResponse() ?? new RedirectResponse(
            $this->urlGenerator->generate($receiver->defaultRoute)
        );
    }

    private function fail(
        Receiver $receiver,
        CrossLoginError $error,
        Request $request,
        ?\Throwable $exception = null,
    ): Response {
        $failed = new CrossLoginFailedEvent($receiver->name, $error, $request, $exception);
        $this->dispatcher->dispatch($failed, $this->eventName($receiver, 'failed'));

        return $failed->getResponse() ?? new RedirectResponse(
            $this->urlGenerator->generate(
                $receiver->defaultRoute,
                [$receiver->errorQueryParameter => $error->value],
            )
        );
    }

    private function currentUser(): ?UserInterface
    {
        return $this->tokenStorage->getToken()?->getUser();
    }

    private function eventName(Receiver $receiver, string $stage): string
    {
        return \sprintf('lingoda_crosslogin.%s.%s', $receiver->name, $stage);
    }
}
