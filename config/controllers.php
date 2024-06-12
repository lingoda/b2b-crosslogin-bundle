<?php

declare(strict_types = 1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Lingoda\CrossLoginBundle\Web\Controller\SignAndRedirectController;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(SignAndRedirectController::class)
                ->arg(0, service(TokenHandler::class))
                ->tag('controller.service_arguments')
    ;
};
