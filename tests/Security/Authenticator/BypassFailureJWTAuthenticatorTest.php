<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\Security\Authenticator;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Lingoda\CrossLoginBundle\Security\Authenticator\BypassFailureJWTAuthenticator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BypassFailureJWTAuthenticatorTest extends TestCase
{
    #[Test]
    public function onAuthenticationFailure(): void
    {
        $request = $this->createMock(Request::class);
        $authenticator = new BypassFailureJWTAuthenticator(
            $this->createMock(JWTTokenManagerInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TokenExtractorInterface::class),
            $this->createMock(UserProviderInterface::class),
            $this->createMock(TranslatorInterface::class)
        );

        self::assertNull($authenticator->onAuthenticationFailure($request, new AuthenticationException()));
    }
}
