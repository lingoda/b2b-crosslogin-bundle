<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Routing;

/**
 * The recommended mount point for the receive route.
 *
 * A sender in another app cannot generate the receiving app's route, so without a shared
 * constant every sender ends up with its own hard-coded string or env var. Receivers are free
 * to import the route under a different prefix, but then the sender must be told.
 */
final class CrossLoginRoutes
{
    public const string RECEIVE_PATH = '/cross-login/receive';

    public const string RECEIVER_ATTRIBUTE = '_crosslogin_receiver';
}
