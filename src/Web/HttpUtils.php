<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Web;

use Lingoda\CrossLoginBundle\ValueObject\UrlParts;

final class HttpUtils
{
    /**
     * Composes a URL from an array of parts.
     *
     * @param UrlParts|array{scheme?:string, host?:string, port?:int, user?:string, pass?:string, query?:string, path?:string, fragment?:string} $urlParts
     */
    public static function composeUrl(UrlParts|array $urlParts): string
    {
        if (is_array($urlParts)) {
            $urlParts = new UrlParts($urlParts);
        }

        $scheme = $urlParts->scheme() ? $urlParts->scheme() . '://' : '';
        $host = $urlParts->host() ?? '';
        $port = $urlParts->port() ? ':' . $urlParts->port() : '';
        $user = $urlParts->user() ?? '';
        $pass = $urlParts->pass() ? ':' . $urlParts->pass() : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $urlParts->path() ?? '';
        $query = $urlParts->query() ? '?' . $urlParts->query() : '';
        $fragment = $urlParts->fragment() ? '#' . $urlParts->fragment() : '';

        return $scheme . $user . $pass . $host . $port . sprintf('/%s', ltrim($path, '/')) . $query . $fragment;
    }
}
