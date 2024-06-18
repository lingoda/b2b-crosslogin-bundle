<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\ValueObject;

final class Url implements \Stringable
{
    public function __construct(
        private string $url
    ) {
        $this->url = urldecode($this->url);
        if (false === filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('Invalid URL "%s"', $this->url));
        }
    }

    public static function fromString(string $url): self
    {
        return new self($url);
    }

    public function encode(): string
    {
        return urlencode($this->url);
    }

    public function value(): string
    {
        return $this->url;
    }

    public function parse(): UrlParts
    {
        return UrlParts::fromUrl($this);
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
