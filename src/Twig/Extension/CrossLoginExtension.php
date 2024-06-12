<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Twig\Extension;

use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CrossLoginExtension extends AbstractExtension
{
    public function __construct(
        private readonly TokenHandler $handler,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('crosslogin_generate_token', [$this->handler, 'generateToken']),
            new TwigFunction('crosslogin_sign_url', [$this->handler, 'getSignedRedirectUrl'], ['is_safe' => ['html']]),
        ];
    }

    public function getName(): string
    {
        return 'crosslogin';
    }
}
