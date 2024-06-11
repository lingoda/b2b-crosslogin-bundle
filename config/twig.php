<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Lingoda\CrossLoginBundle\Twig\Extension\CrossLoginExtension;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(CrossLoginExtension::class)
                ->arg(0, service(TokenHandler::class))
                ->tag('twig.extension')
    ;
};
