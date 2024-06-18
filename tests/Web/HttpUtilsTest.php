<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\Web;

use Lingoda\CrossLoginBundle\ValueObject\UrlParts;
use Lingoda\CrossLoginBundle\Web\HttpUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpUtilsTest extends TestCase
{
    /**
     * @return iterable<string[]>
     */
    public static function provideComposeUrl(): iterable
    {
        yield ['https://example.com?foo=bar', 'https://example.com/?foo=bar'];
        yield ['https://example.com/foo', 'https://example.com/foo'];
        yield ['https://example.com/foo?foo=bar', 'https://example.com/foo?foo=bar'];
        yield ['https://example.com/foo?foo=bar&0=integer', 'https://example.com/foo?foo=bar&0=integer'];
        yield ['https://example.com/foo?foo=bar&0=integer#foobar', 'https://example.com/foo?foo=bar&0=integer#foobar'];
        yield ['https://example.com:12345/foo?foo=bar&0=integer#foobar', 'https://example.com:12345/foo?foo=bar&0=integer#foobar'];
        yield ['https://username:password@example.com:12345/foo?foo=bar&0=integer#foobar', 'https://username:password@example.com:12345/foo?foo=bar&0=integer#foobar'];
    }

    #[Test]
    #[DataProvider('provideComposeUrl')]
    public function composeUrl(string $url, string $expected): void
    {
        /** @var array{scheme?:string, host?:string, port?:int, user?:string, pass?:string, query?:string, path?:string, fragment?:string} $parts */
        $parts = parse_url($url);

        self::assertSame($expected, HttpUtils::composeUrl($parts));
        self::assertSame($expected, HttpUtils::composeUrl(UrlParts::fromUrl($url)));
    }
}
