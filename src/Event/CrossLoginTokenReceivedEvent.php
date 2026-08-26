<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Dispatched once the token's claims are decoded, before any response is decided.
 *
 * The user is whatever the firewall resolved. It is null when the receiving app does not know
 * the identity yet, because BypassFailureJWTAuthenticator lets an unresolvable token through
 * instead of returning 401. A listener may create the user from the claims and call setUser().
 *
 * Dispatched as `lingoda_crosslogin.<receiver>.token_received`, by the receive controller and
 * nowhere else. An app that configures no receiver never sees it.
 */
final class CrossLoginTokenReceivedEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $receiver,
        public readonly array $payload,
        public readonly Request $request,
        private ?UserInterface $user = null,
    ) {
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }
}
