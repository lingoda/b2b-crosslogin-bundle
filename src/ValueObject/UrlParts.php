<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\ValueObject;

use Lingoda\CrossLoginBundle\Web\HttpUtils;

final readonly class UrlParts implements \Stringable
{
    /**
     * @param array{scheme?:string, host?:string, port?:int, user?:string, pass?:string, query?:string, path?:string, fragment?:string} $parts
     */
    public function __construct(
        private array $parts
    ) {
    }

    public static function fromUrl(string|Url $url): self
    {
        /** @var array{scheme?:string, host?:string, port?:int, user?:string, pass?:string, query?:string, path?:string, fragment?:string} $parts */
        $parts = parse_url((string) $url);

        return new self($parts);
    }

    public function scheme(): ?string
    {
        return $this->parts['scheme'] ?? null;
    }

    public function host(): ?string
    {
        return $this->parts['host'] ?? null;
    }

    public function port(): ?int
    {
        return $this->parts['port'] ?? null;
    }

    public function user(): ?string
    {
        return $this->parts['user'] ?? null;
    }

    public function pass(): ?string
    {
        return $this->parts['pass'] ?? null;
    }

    public function query(): ?string
    {
        return $this->parts['query'] ?? null;
    }

    public function path(): ?string
    {
        return $this->parts['path'] ?? null;
    }

    public function fragment(): ?string
    {
        return $this->parts['fragment'] ?? null;
    }

    public function compose(): string
    {
        return HttpUtils::composeUrl($this->parts);
    }

    /**
     * @return array{scheme?:string, host?:string, port?:int, user?:string, pass?:string, query?:string, path?:string, fragment?:string}
     */
    public function toArray(): array
    {
        return $this->parts;
    }

    public function __toString(): string
    {
        return $this->compose();
    }
}
