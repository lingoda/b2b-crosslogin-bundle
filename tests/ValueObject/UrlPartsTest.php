<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\ValueObject;

use Lingoda\CrossLoginBundle\ValueObject\UrlParts;
use PHPUnit\Framework\TestCase;

class UrlPartsTest extends TestCase
{
    public function testInit(): void
    {
        $urlParts = new UrlParts([
            'scheme' => 'https',
            'host' => 'example.com',
            'port' => 80,
            'user' => 'user',
            'pass' => 'pass',
            'path' => 'path',
            'query' => 'a=1&b=2',
            'fragment' => 'fragment',
        ]);
        self::assertSame('https', $urlParts->scheme());
        self::assertSame('example.com', $urlParts->host());
        self::assertSame(80, $urlParts->port());
        self::assertSame('user', $urlParts->user());
        self::assertSame('pass', $urlParts->pass());
        self::assertSame('path', $urlParts->path());
        self::assertSame('a=1&b=2', $urlParts->query());
        self::assertSame('fragment', $urlParts->fragment());
        self::assertSame('https://user:pass@example.com:80/path?a=1&b=2#fragment', (string) $urlParts);
        self::assertSame('https://user:pass@example.com:80/path?a=1&b=2#fragment', $urlParts->compose());

        $urlParts = UrlParts::fromUrl('https://example.com');
        self::assertSame('https', $urlParts->scheme());
        self::assertSame('example.com', $urlParts->host());
        self::assertNull($urlParts->port());
        self::assertNull($urlParts->user());
        self::assertNull($urlParts->pass());
        self::assertNull($urlParts->path());
        self::assertNull($urlParts->query());
        self::assertNull($urlParts->fragment());
        self::assertSame('https://example.com/', (string) $urlParts);
        self::assertSame('https://example.com/', $urlParts->compose());
    }
}
