<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Dispatched when the hand-off resolved a user.
 *
 * This is where a stateless app turns the one-shot cross-login token into its own durable
 * session — typically minting cookies and setting them on a redirect. A listener that sets a
 * response owns the outcome; otherwise the controller redirects to the receiver's
 * default_route.
 *
 * Dispatched as `lingoda_crosslogin.<receiver>.succeeded`, by the receive controller and
 * nowhere else. An app that configures no receiver never sees it.
 */
final class CrossLoginSucceededEvent
{
    private ?Response $response = null;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $receiver,
        public readonly UserInterface $user,
        public readonly array $payload,
        public readonly Request $request,
    ) {
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
