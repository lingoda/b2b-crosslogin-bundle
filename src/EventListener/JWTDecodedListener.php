<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;

final readonly class JWTDecodedListener
{
    /**
     * @param string[] $acceptedAudiences hosts this app answers to; empty falls back to [$issuer]
     */
    public function __construct(
        private string $issuer,
        private array $acceptedAudiences = [],
    ) {
    }

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();
        if (!isset($payload['aud']) || !isset($payload['iss'])) {
            return;
        }

        // The token's audience(s) must include one this app answers to. Empty config
        // falls back to [issuer] (single-domain back-compat); a multi-host app lists
        // its own hosts so same-app cross-host tokens (aud = sibling host) validate.
        $audiences = $this->getAudiences($payload['aud']);
        $accepted = $this->acceptedAudiences !== [] ? $this->acceptedAudiences : [$this->issuer];
        if (!$audiences || array_intersect($audiences, $accepted) === []) {
            $event->markAsInvalid();
        }
    }

    /**
     * @param string|string[] $audience
     *
     * @return array<string>|null
     */
    private function getAudiences(string|array $audience): ?array
    {
        if (is_string($audience)) {
            return [$audience];
        }

        $audience = array_filter($audience);
        if (empty($audience)) {
            return null;
        }

        return $audience;
    }
}
