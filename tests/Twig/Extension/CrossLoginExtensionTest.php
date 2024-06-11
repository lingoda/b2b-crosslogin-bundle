<?php

namespace Lingoda\CrossLoginBundle\Tests\Twig\Extension;

use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Lingoda\CrossLoginBundle\Twig\Extension\CrossLoginExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class CrossLoginExtensionTest extends TestCase
{
    /** @phpstan-ignore-next-line */
    private TokenHandler&MockObject $handler;
    private CrossLoginExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();

        /** @phpstan-ignore-next-line */
        $this->handler = $this->createMock(TokenHandler::class);
        $this->extension = new CrossLoginExtension($this->handler);
    }

    #[Test]
    public function getFunctions(): void
    {
        self::assertEquals(
            [
                new TwigFunction('crosslogin_generate_token', [$this->handler, 'generateToken']),
                new TwigFunction('crosslogin_sign_url', [$this->handler, 'signUrl'], ['is_safe' => ['html']]),
            ],
            $this->extension->getFunctions(),
        );
    }

    #[Test]
    public function getName(): void
    {
        self::assertEquals('crosslogin', $this->extension->getName());
    }
}
