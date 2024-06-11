<?php

namespace Lingoda\CrossLoginBundle\Tests\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenHandlerTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private JWTTokenManagerInterface&MockObject $jwtTokenManager;
    private TokenHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->jwtTokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->handler = new TokenHandler($this->tokenStorage, $this->jwtTokenManager, 'token', 'issuer');
    }

    #[Test]
    public function itThrowsExceptionOnEmptyParamName(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Token parameter name must not be empty');

        new TokenHandler($this->tokenStorage, $this->jwtTokenManager, '', null);
    }

    #[Test]
    public function itThrowsExceptionOnNotLoggedInUserWhenGeneratingToken(): void
    {
        self::expectException(AccessDeniedException::class);
        self::expectExceptionMessage('There is no logged-in user');

        $this->handler->generateToken();
    }

    #[Test]
    public function generateToken(): void
    {
        $user = $this->createMock(UserInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects(self::once())->method('getToken')->willReturn($token);
        $this->jwtTokenManager
            ->expects(self::once())
            ->method('createFromPayload')
            ->with($user, ['iss' => 'issuer'])
            ->willReturn('some-token')
        ;

        self::assertEquals('some-token', $this->handler->generateToken());
    }

    #[Test]
    public function itThrowsExceptionOnInvalidUrlWhenSigningUrl(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid URL "invalid-url"');

        $this->handler->signUrl('invalid-url');
    }

    #[Test]
    public function signUrl(): void
    {
        $user = $this->createMock(UserInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->jwtTokenManager
            ->method('createFromPayload')
            ->with($user, ['iss' => 'issuer', 'aud' => 'example.com'])
            ->willReturn('some-token')
        ;

        self::assertEquals('https://example.com?token=some-token', $this->handler->signUrl('https://example.com'));
        self::assertEquals('https://example.com?foo=bar&token=some-token', $this->handler->signUrl('https://example.com?foo=bar'));
        self::assertEquals('https://example.com?token=some-token', $this->handler->signUrl('https://example.com?token=original'));
    }
}
