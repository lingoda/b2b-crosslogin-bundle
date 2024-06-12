<?php

declare(strict_types = 1);

use Lingoda\CrossLoginBundle\Web\Controller\SignAndRedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('lingoda_crosslogin_sign_and_redirect', '/crosslogin/sign-and-redirect')
        ->controller(SignAndRedirectController::class)
        ->methods(['GET'])
    ;
};
