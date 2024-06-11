<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(TokenHandler::class)
                ->arg(0, service(TokenStorageInterface::class))
                ->arg(1, service(JWTTokenManagerInterface::class))
                ->arg(2, param('lingoda_cross_login.query_parameter_name'))
                ->arg(3, param('lingoda_cross_login.issuer'))
    ;
};
