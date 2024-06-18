<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\ValueObject;

use Lingoda\CrossLoginBundle\ValueObject\Url;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    #[Test]
    public function init(): void
    {
        $url = new Url('https://example.com');
        self::assertSame('https://example.com', $url->__toString());
        self::assertSame('https://example.com', $url->value());
        self::assertSame('https%3A%2F%2Fexample.com', $url->encode());
        self::assertSame(['scheme' => 'https', 'host' => 'example.com'], $url->parse()->toArray());

        $url = Url::fromString(urlencode('https://example.com'));
        self::assertSame('https://example.com', $url->__toString());
        self::assertSame('https://example.com', $url->value());
        self::assertSame('https%3A%2F%2Fexample.com', $url->encode());
        self::assertSame(['scheme' => 'https', 'host' => 'example.com'], $url->parse()->toArray());
    }

    #[Test]
    public function initWithInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL "example.com"');
        new Url('example.com');
    }
}
