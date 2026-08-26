<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatched when the hand-off produced no user: no token, an unusable token, or claims that
 * no listener could turn into an identity.
 *
 * A listener that sets a response owns the outcome — usually a redirect to the app's own login.
 * Otherwise the controller redirects to the receiver's default_route with the error code in a
 * query parameter.
 *
 * Dispatched as `lingoda_crosslogin.<receiver>.failed`, by the receive controller and
 * nowhere else. An app that configures no receiver never sees it.
 */
final class CrossLoginFailedEvent
{
    private ?Response $response = null;

    public function __construct(
        public readonly string $receiver,
        public readonly CrossLoginError $error,
        public readonly Request $request,
        public readonly ?\Throwable $exception = null,
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
