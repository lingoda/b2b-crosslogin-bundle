<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\ValueObject\Audience;
use Lingoda\CrossLoginBundle\ValueObject\Url;
use Lingoda\CrossLoginBundle\ValueObject\UrlParts;
use Lingoda\CrossLoginBundle\Web\HttpUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Webmozart\Assert\Assert;

final readonly class TokenHandler
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private JWTTokenManagerInterface $jwtTokenManager,
        private UrlGeneratorInterface $urlGenerator,
        private string $tokenParamName,
        private string $issuer,
        private ?int $ttl,
    ) {
        Assert::stringNotEmpty($this->tokenParamName, 'Token parameter name must not be empty');
    }

    public function generateToken(string|Url $url): string
    {
        if (null === $user = $this->tokenStorage->getToken()?->getUser()) {
            throw new AccessDeniedException('There is no logged-in user');
        }

        return $this->jwtTokenManager->createFromPayload($user, $this->createPayload(Audience::fromUrl($url)));
    }

    public function signUrl(string|Url $url): string
    {
        $url = $this->buildUrl($url);
        $urlParts = $url->parse();

        parse_str($urlParts->query() ?? '', $queryParams);
        $queryParams[$this->tokenParamName] = $this->generateToken($url);

        $parts = $urlParts->toArray();
        $parts['query'] = http_build_query($queryParams);

        return HttpUtils::composeUrl(new UrlParts($parts));
    }

    public function getSignedRedirectUrl(string|Url $url): string
    {
        return $this->urlGenerator->generate('lingoda_crosslogin_sign_and_redirect', [
            'url' => $this->buildUrl($url)->encode()
        ]);
    }

    /**
     * @return array{iss:string, aud:string, exp?:int}
     */
    private function createPayload(Audience $audience): array
    {
        $issuer = ['iss' => $this->issuer];
        $audience = ['aud' => $audience->value()];
        $expiration = null !== $this->ttl ? ['exp' => time() + $this->ttl] : [];

        return $issuer + $audience + $expiration;
    }

    private function buildUrl(string|Url $url): Url
    {
        return $url instanceof Url ? $url : Url::fromString($url);
    }
}
