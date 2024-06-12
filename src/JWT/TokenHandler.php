<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\Web\HttpUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Webmozart\Assert\Assert;

final readonly class TokenHandler
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private JWTTokenManagerInterface $jwtTokenManager,
        private string $tokenParamName,
        private ?string $issuer,
        private ?int $ttl,
    ) {
        Assert::stringNotEmpty($this->tokenParamName, 'Token parameter name must not be empty');
    }

    public function generateToken(?string $audience = null): string
    {
        if (null === $user = $this->tokenStorage->getToken()?->getUser()) {
            throw new AccessDeniedException('There is no logged-in user');
        }

        return $this->jwtTokenManager->createFromPayload($user, $this->createPayload($audience));
    }

    public function signUrl(string $url): string
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('Invalid URL "%s"', $url));
        }

        /** @var array<string, string> $parts */
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $params);
        $params[$this->tokenParamName] = $this->generateToken($parts['host'] ?? null);
        $parts['query'] = http_build_query($params);

        return HttpUtils::composeUrl($parts);
    }

    /**
     * @return array{iss?:string, aud?:string}
     */
    private function createPayload(?string $audience): array
    {
        $issuer = isset($this->issuer) ? ['iss' => $this->issuer] : [];
        $audience = null !== $audience ? ['aud' => $audience] : [];
        $expiration = null !== $this->ttl ? ['exp' => time() + $this->ttl] : [];

        return $issuer + $audience + $expiration;
    }
}
