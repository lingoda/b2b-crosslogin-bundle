<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\ValueObject;

use Lingoda\CrossLoginBundle\ValueObject\Audience;
use Lingoda\CrossLoginBundle\ValueObject\Url;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AudienceTest extends TestCase
{
    #[Test]
    public function fromString(): void
    {
        $audience = Audience::fromUrl('https://example.com:8080/?query=string#fragment');
        self::assertSame('example.com:8080', $audience->value());
        self::assertSame('example.com:8080', (string) $audience);
    }

    #[Test]
    public function fromUrl(): void
    {
        $audience = Audience::fromUrl(new Url('https://example.com:8080/?query=string#fragment'));
        self::assertSame('example.com:8080', $audience->value());
        self::assertSame('example.com:8080', (string) $audience);
    }

    #[Test]
    public function fromUrlWithoutPort(): void
    {
        $audience = Audience::fromUrl(new Url('https://example.com/?query=string#fragment'));
        self::assertSame('example.com', $audience->value());
        self::assertSame('example.com', (string) $audience);
    }

    #[Test]
    public function withInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL "example.com"');
        Audience::fromUrl('example.com');
    }
}
