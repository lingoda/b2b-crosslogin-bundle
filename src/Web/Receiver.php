<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Web;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Webmozart\Assert\Assert;

/**
 * One receiving endpoint's resolved settings.
 *
 * A single kernel can serve several hosts, each landing its own hand-off, so a receiver is named
 * and configured on its own and the route says which one it belongs to.
 *
 * The JWT manager is per-receiver because an app may verify cross-login tokens with a different
 * key than the one it signs its own session tokens with.
 */
final readonly class Receiver
{
    public function __construct(
        public string $name,
        public string $defaultRoute,
        public string $errorQueryParameter,
        public JWTTokenManagerInterface $jwtManager,
    ) {
        Assert::stringNotEmpty($this->name, 'Receiver name must not be empty');
        Assert::stringNotEmpty($this->defaultRoute, 'Receiver default_route must not be empty');
        Assert::stringNotEmpty($this->errorQueryParameter, 'Receiver error_query_parameter must not be empty');
    }
}
