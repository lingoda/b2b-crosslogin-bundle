<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\Web\Controller;

use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Lingoda\CrossLoginBundle\Web\Controller\SignAndRedirectController;
use PHPUnit\Framework\TestCase;

class SignAndRedirectControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        /** @phpstan-ignore-next-line  */
        $tokenHandler = $this->createMock(TokenHandler::class);
        $tokenHandler->expects(self::once())
            ->method('signUrl')
            ->with('https://example.com')
            ->willReturn('https://example.com?token=123')
        ;

        $controller = new SignAndRedirectController($tokenHandler);
        $response = $controller('https://example.com');

        self::assertSame('https://example.com?token=123', $response->getTargetUrl());
    }
}
