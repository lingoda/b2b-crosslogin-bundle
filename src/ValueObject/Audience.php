<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Audience implements \Stringable
{
    private function __construct(
        private string $audience
    ) {
        Assert::stringNotEmpty($this->audience);
    }

    public static function fromUrl(string|Url $url): self
    {
        $parts = Url::fromString((string) $url)->parse();
        $audience = $parts->host() . ($parts->port() ? ':' . $parts->port() : '');

        return new self($audience);
    }

    public function value(): string
    {
        return $this->audience;
    }

    public function __toString(): string
    {
        return $this->audience;
    }
}
