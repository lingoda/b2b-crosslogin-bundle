<?php

declare(strict_types = 1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\EventListener\JWTDecodedListener;
use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(TokenHandler::class)
                ->arg(0, service(TokenStorageInterface::class))
                ->arg(1, service(JWTTokenManagerInterface::class))
                ->arg(2, service(UrlGeneratorInterface::class))
                ->arg(3, param('lingoda_cross_login.query_parameter_name'))
                ->arg(4, param('lingoda_cross_login.issuer'))
                ->arg(5, param('lingoda_cross_login.token_ttl'))
            ->set(JWTDecodedListener::class)
                ->arg(0, param('lingoda_cross_login.issuer'))
                ->tag('kernel.event_listener', ['event' => 'lexik_jwt_authentication.on_jwt_decoded', 'method' => 'onJWTDecoded'])
    ;
};
