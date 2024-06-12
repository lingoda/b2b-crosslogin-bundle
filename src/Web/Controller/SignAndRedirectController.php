<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Web\Controller;

use Lingoda\CrossLoginBundle\JWT\TokenHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

final readonly class SignAndRedirectController
{
    public function __construct(
        private TokenHandler $tokenHandler
    ) {
    }

    public function __invoke(
        #[MapQueryParameter] string $url,
    ): RedirectResponse {
        return new RedirectResponse($this->tokenHandler->signUrl($url));
    }
}
