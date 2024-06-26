<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Lingoda\CrossLoginBundle\ValueObject\Url;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenHandlerTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private JWTTokenManagerInterface&MockObject $jwtTokenManager;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private TokenHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->jwtTokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->handler = new TokenHandler(
            $this->tokenStorage,
            $this->jwtTokenManager,
            $this->urlGenerator,
            'token',
            'issuer',
            300
        );
    }

    #[Test]
    public function itThrowsExceptionOnEmptyParamName(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Token parameter name must not be empty');

        new TokenHandler($this->tokenStorage, $this->jwtTokenManager, $this->urlGenerator, '', 'issuer', null);
    }

    #[Test]
    public function itThrowsExceptionOnNotLoggedInUserWhenGeneratingToken(): void
    {
        self::expectException(AccessDeniedException::class);
        self::expectExceptionMessage('There is no logged-in user');

        $this->handler->generateToken('example.com');
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
            ->with($user, ['iss' => 'issuer', 'exp' => time() + 300, 'aud' => 'example.com:8080'])
            ->willReturn('some-token')
        ;

        self::assertEquals('some-token', $this->handler->generateToken('https://example.com:8080'));
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
            ->with($user, ['iss' => 'issuer', 'aud' => 'example.com', 'exp' => time() + 300])
            ->willReturn('some-token')
        ;

        self::assertEquals('https://example.com/?token=some-token', $this->handler->signUrl(urlencode('https://example.com')));
        self::assertEquals('https://example.com/?foo=bar&token=some-token', $this->handler->signUrl(Url::fromString(urlencode('https://example.com?foo=bar'))));
        self::assertEquals('https://example.com/?token=some-token', $this->handler->signUrl(Url::fromString(urlencode('https://example.com?token=original'))));
    }

    #[Test]
    public function signAndRedirectUrl(): void
    {
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('lingoda_crosslogin_sign_and_redirect', ['url' => urlencode('https://example.com')])
            ->willReturn('/redirect-url?url=https%3A%2F%2Fexample.com')
        ;
        self::assertEquals('/redirect-url?url=https%3A%2F%2Fexample.com', $this->handler->getSignedRedirectUrl('https://example.com'));
    }
}
