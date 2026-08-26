<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Web;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\Web\Exception\UnknownReceiverException;
use Psr\Container\ContainerInterface;

/**
 * Resolves the receiver named by a route into its settings.
 *
 * Receivers are opt-in: an app that configures none has no receive endpoint at all, which is
 * what keeps this feature invisible to apps that cross-login into a stateful firewall and never
 * needed a landing endpoint.
 *
 * @phpstan-type ReceiverConfig array{default_route: string, error_query_parameter: string, jwt_manager: string}
 */
final readonly class Receivers
{
    /**
     * @param array<string, ReceiverConfig> $receivers
     * @param ContainerInterface $jwtManagers service locator keyed by receiver name
     */
    public function __construct(
        private array $receivers,
        private ContainerInterface $jwtManagers,
    ) {
    }

    /**
     * @throws UnknownReceiverException when the route names a receiver that is not configured
     */
    public function get(string $name): Receiver
    {
        if (!isset($this->receivers[$name])) {
            throw UnknownReceiverException::create($name, array_keys($this->receivers));
        }

        $config = $this->receivers[$name];
        $jwtManager = $this->jwtManagers->get($name);
        if (!$jwtManager instanceof JWTTokenManagerInterface) {
            throw UnknownReceiverException::badJwtManager($name, $config['jwt_manager']);
        }

        return new Receiver(
            $name,
            $config['default_route'],
            $config['error_query_parameter'],
            $jwtManager,
        );
    }
}
