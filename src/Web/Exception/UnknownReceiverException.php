<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Web\Exception;

/**
 * The receive route named a receiver that `lingoda_cross_login.receivers` does not define.
 *
 * Almost always a half-finished opt-in: the route file was imported but the matching config key
 * was never added, so the message lists what is configured.
 */
final class UnknownReceiverException extends \LogicException
{
    /**
     * @param string[] $configured
     */
    public static function create(string $name, array $configured): self
    {
        return new self(\sprintf(
            'Cross-login receiver "%s" is not configured. Add it under "lingoda_cross_login.receivers". Configured receivers: %s.',
            $name,
            $configured === [] ? '<none>' : implode(', ', $configured),
        ));
    }

    public static function badJwtManager(string $name, string $serviceId): self
    {
        return new self(\sprintf(
            'Service "%s" configured as jwt_manager for cross-login receiver "%s" does not implement %s.',
            $serviceId,
            $name,
            \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface::class,
        ));
    }
}
