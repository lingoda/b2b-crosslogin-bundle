<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\Web\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\Event\CrossLoginError;
use Lingoda\CrossLoginBundle\Event\CrossLoginFailedEvent;
use Lingoda\CrossLoginBundle\Event\CrossLoginSucceededEvent;
use Lingoda\CrossLoginBundle\Event\CrossLoginTokenReceivedEvent;
use Lingoda\CrossLoginBundle\Routing\CrossLoginRoutes;
use Lingoda\CrossLoginBundle\Web\Controller\ReceiveController;
use Lingoda\CrossLoginBundle\Web\Receivers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReceiveControllerTest extends TestCase
{
    /** @var list<array{0: object, 1: string}> */
    private array $dispatched = [];

    #[Test]
    public function missingTokenFailsWithoutParsingAnything(): void
    {
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->expects(self::never())->method('parse');

        $response = $this->invoke($this->request(token: null), $jwtManager);

        self::assertRedirectsTo('/first-app?crosslogin_error=missing_token', $response);
        self::assertSame(
            ['lingoda_crosslogin.first_app.token_received', 'lingoda_crosslogin.first_app.failed'],
            $this->dispatchedNames()
        );
        self::assertSame(CrossLoginError::MissingToken, $this->failedEvent()->error);
    }

    #[Test]
    public function anAlreadyAuthenticatedVisitorNeedsNoTokenAtAll(): void
    {
        // lexik's cookie extractor can authenticate a return visit that carries no query
        // parameter. Requiring the token here would bounce a valid session to the login page.
        $user = new InMemoryUser('ada@example.com', null);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->expects(self::never())->method('parse');

        $response = $this->invoke($this->request(token: null), $jwtManager, user: $user);

        self::assertRedirectsTo('/first-app', $response);
        self::assertSame(
            ['lingoda_crosslogin.first_app.token_received', 'lingoda_crosslogin.first_app.succeeded'],
            $this->dispatchedNames()
        );
    }

    #[Test]
    public function anUnusableTokenDoesNotUnseatAnAuthenticatedVisitor(): void
    {
        $user = new InMemoryUser('ada@example.com', null);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->method('parse')->willThrowException(new \RuntimeException('bad signature'));

        $response = $this->invoke($this->request(), $jwtManager, user: $user);

        self::assertRedirectsTo('/first-app', $response);
    }

    #[Test]
    public function unparsableTokenFailsAndCarriesTheCause(): void
    {
        $cause = new \RuntimeException('bad signature');
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->method('parse')->willThrowException($cause);

        $response = $this->invoke($this->request(), $jwtManager);

        self::assertRedirectsTo('/first-app?crosslogin_error=invalid_token', $response);
        self::assertSame(CrossLoginError::InvalidToken, $this->failedEvent()->error);
        self::assertSame($cause, $this->failedEvent()->exception);
    }

    #[Test]
    public function validTokenWithNoResolvableUserFails(): void
    {
        $response = $this->invoke($this->request(), $this->parsingManager());

        self::assertRedirectsTo('/first-app?crosslogin_error=unknown_user', $response);
        self::assertSame(
            ['lingoda_crosslogin.first_app.token_received', 'lingoda_crosslogin.first_app.failed'],
            $this->dispatchedNames()
        );
        self::assertSame(CrossLoginError::UnknownUser, $this->failedEvent()->error);
    }

    #[Test]
    public function aListenerMayProvisionTheUserFromTheClaims(): void
    {
        $user = new InMemoryUser('ada@example.com', null);

        $response = $this->invoke(
            $this->request(),
            $this->parsingManager(),
            onEvent: static function (object $event) use ($user): void {
                if ($event instanceof CrossLoginTokenReceivedEvent) {
                    self::assertSame('ada@example.com', $event->payload['username'] ?? null);
                    $event->setUser($user);
                }
            }
        );

        self::assertRedirectsTo('/first-app', $response);
        self::assertSame(
            ['lingoda_crosslogin.first_app.token_received', 'lingoda_crosslogin.first_app.succeeded'],
            $this->dispatchedNames()
        );
    }

    #[Test]
    public function theFirewallUserIsUsedWhenNoListenerProvisions(): void
    {
        $user = new InMemoryUser('ada@example.com', null);

        $response = $this->invoke($this->request(), $this->parsingManager(), user: $user);

        self::assertRedirectsTo('/first-app', $response);
        $succeeded = $this->dispatched[1][0];
        self::assertInstanceOf(CrossLoginSucceededEvent::class, $succeeded);
        self::assertSame($user, $succeeded->user);
        self::assertSame('first_app', $succeeded->receiver);
    }

    #[Test]
    public function aSucceededListenerOwnsTheResponse(): void
    {
        $own = new RedirectResponse('/somewhere-else');

        $response = $this->invoke(
            $this->request(),
            $this->parsingManager(),
            user: new InMemoryUser('ada@example.com', null),
            onEvent: static function (object $event) use ($own): void {
                if ($event instanceof CrossLoginSucceededEvent) {
                    $event->setResponse($own);
                }
            }
        );

        self::assertSame($own, $response);
    }

    #[Test]
    public function aFailedListenerOwnsTheResponse(): void
    {
        $own = new RedirectResponse('/saml/login');

        $response = $this->invoke(
            $this->request(token: null),
            $this->createMock(JWTTokenManagerInterface::class),
            onEvent: static function (object $event) use ($own): void {
                if ($event instanceof CrossLoginFailedEvent) {
                    $event->setResponse($own);
                }
            }
        );

        self::assertSame($own, $response);
    }

    /**
     * @param \Closure(object): void|null $onEvent stands in for the app's listeners
     */
    private function invoke(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        ?UserInterface $user = null,
        ?\Closure $onEvent = null,
    ): Response {
        $this->dispatched = [];

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            function (object $event, ?string $name = null) use ($onEvent): object {
                $this->dispatched[] = [$event, (string) $name];
                if ($onEvent !== null) {
                    $onEvent($event);
                }

                return $event;
            }
        );

        $controller = new ReceiveController(
            new Receivers(
                ['first_app' => [
                    'default_route' => 'first_app_main',
                    'error_query_parameter' => 'crosslogin_error',
                    'jwt_manager' => 'lexik_jwt_authentication.jwt_manager',
                ]],
                new ServiceLocator(['first_app' => static fn (): JWTTokenManagerInterface => $jwtManager]),
            ),
            $this->tokenStorage($user),
            $dispatcher,
            $this->urlGenerator(),
            'bearer',
        );

        return $controller($request);
    }

    private function request(?string $token = 'a.b.c'): Request
    {
        $request = new Request($token === null ? [] : ['bearer' => $token]);
        $request->attributes->set(CrossLoginRoutes::RECEIVER_ATTRIBUTE, 'first_app');

        return $request;
    }

    private function parsingManager(): JWTTokenManagerInterface
    {
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->method('parse')->willReturn(['username' => 'ada@example.com']);

        return $jwtManager;
    }

    private function tokenStorage(?UserInterface $user): TokenStorageInterface
    {
        $storage = new TokenStorage();
        if ($user !== null) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            $storage->setToken($token);
        }

        return $storage;
    }

    private function urlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static function (string $route, array $parameters = []): string {
                self::assertSame('first_app_main', $route);

                return '/first-app' . ($parameters === [] ? '' : '?' . http_build_query($parameters));
            }
        );

        return $urlGenerator;
    }

    /**
     * @return list<string>
     */
    private function dispatchedNames(): array
    {
        return array_map(static fn (array $entry): string => $entry[1], $this->dispatched);
    }

    private function failedEvent(): CrossLoginFailedEvent
    {
        self::assertNotEmpty($this->dispatched, 'No event was dispatched');
        $last = $this->dispatched[\count($this->dispatched) - 1][0];
        self::assertInstanceOf(CrossLoginFailedEvent::class, $last);

        return $last;
    }

    private static function assertRedirectsTo(string $expected, Response $response): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($expected, $response->getTargetUrl());
    }
}
