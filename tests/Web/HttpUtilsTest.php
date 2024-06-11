<?php

namespace Lingoda\CrossLoginBundle\Tests\Web;

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
        yield ['https://example.com/foo'];
        yield ['https://example.com/foo?foo=bar'];
        yield ['https://example.com/foo?foo=bar&0=integer'];
        yield ['https://example.com/foo?foo=bar&0=integer#foobar'];
        yield ['https://example.com:12345/foo?foo=bar&0=integer#foobar'];
        yield ['https://username:password@example.com:12345/foo?foo=bar&0=integer#foobar'];
    }

    #[Test]
    #[DataProvider('provideComposeUrl')]
    public function composeUrl(string $url): void
    {
        /** @var array<string, string> $parts */
        $parts = parse_url($url);

        self::assertEquals($url, HttpUtils::composeUrl($parts));
    }
}
