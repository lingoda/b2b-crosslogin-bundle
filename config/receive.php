<?php

declare(strict_types = 1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lingoda\CrossLoginBundle\Web\Controller\ReceiveController;
use Lingoda\CrossLoginBundle\Web\Receivers;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The Receivers service is registered in LingodaCrossLoginBundle::loadExtension() instead of
 * here, because its second argument is a service locator built from the configured receiver
 * names and that needs the ContainerBuilder.
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set(ReceiveController::class)
                ->arg(0, service(Receivers::class))
                ->arg(1, service(TokenStorageInterface::class))
                ->arg(2, service('event_dispatcher'))
                ->arg(3, service(UrlGeneratorInterface::class))
                ->arg(4, param('lingoda_cross_login.query_parameter_name'))
                ->tag('controller.service_arguments')
    ;
};
