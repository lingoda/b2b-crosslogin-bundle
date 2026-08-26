<?php

declare(strict_types = 1);

use Lingoda\CrossLoginBundle\Web\Controller\ReceiveController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Import this once per receiver, supplying the prefix, host and name prefix yourself:
 *
 *     my_cross_login:
 *         resource: '@LingodaCrossLoginBundle/config/receive_routes.php'
 *         host: '%my_public_domain%'
 *         prefix: /cross-login
 *         name_prefix: 'my_cross_login_'
 *         defaults: { _crosslogin_receiver: my_receiver }
 *
 * The route is deliberately not registered by the bundle: a receiver may need a host
 * constraint, and in an app with a catch-all route it must be registered before that catch-all.
 * The short name requires a name_prefix so several receivers can coexist in one app.
 */
return function (RoutingConfigurator $routes): void {
    $routes->add('receive', '/receive')
        ->controller(ReceiveController::class)
        ->methods(['GET'])
    ;
};
