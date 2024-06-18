<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;

final readonly class JWTDecodedListener
{
    public function __construct(
        private string $issuer,
    ) {
    }

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();
        if (!isset($payload['aud']) || !isset($payload['iss'])) {
            return;
        }

        // audiences of the token must match the issuer configured in the application
        $audiences = $this->getAudiences($payload['aud']);
        if (!$audiences || !in_array($this->issuer, $audiences, true)) {
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
