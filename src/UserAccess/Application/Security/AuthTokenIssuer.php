<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Security;

use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AuthTokenIssuer
{
    public function __construct(
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        #[Autowire('%user_access.refresh_token_ttl%')]
        private string $refreshTokenTtl,
    ) {
    }

    public function issueFor(User $user): TokenPair
    {
        $plainRefreshToken = $this->refreshTokenGenerator->generate();
        $refreshToken = RefreshToken::issue(
            user: $user,
            tokenHash: $this->refreshTokenHasher->hash($plainRefreshToken),
            expiresAt: new \DateTimeImmutable($this->refreshTokenTtl),
        );

        $this->refreshTokenRepository->saveAll([$refreshToken]);

        return new TokenPair(
            accessToken: $this->accessTokenIssuer->issue($user),
            refreshToken: $plainRefreshToken,
            expiresIn: $this->accessTokenIssuer->expiresIn(),
        );
    }

    public function rotate(RefreshToken $currentRefreshToken): TokenPair
    {
        $plainRefreshToken = $this->refreshTokenGenerator->generate();
        $newRefreshTokenHash = $this->refreshTokenHasher->hash($plainRefreshToken);
        $newRefreshToken = RefreshToken::issue(
            user: $currentRefreshToken->getUser(),
            tokenHash: $newRefreshTokenHash,
            expiresAt: new \DateTimeImmutable($this->refreshTokenTtl),
        );

        $currentRefreshToken->revoke($newRefreshTokenHash);
        $this->refreshTokenRepository->saveAll([$currentRefreshToken, $newRefreshToken]);

        return new TokenPair(
            accessToken: $this->accessTokenIssuer->issue($currentRefreshToken->getUser()),
            refreshToken: $plainRefreshToken,
            expiresIn: $this->accessTokenIssuer->expiresIn(),
        );
    }
}
