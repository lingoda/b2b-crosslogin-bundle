<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Security\Authenticator;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BypassFailureJWTAuthenticator extends JWTAuthenticator
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        parent::onAuthenticationFailure($request, $exception);

        return null;
    }
}
