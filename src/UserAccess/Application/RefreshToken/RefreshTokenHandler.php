<?php

declare(strict_types=1);

namespace App\UserAccess\Application\RefreshToken;

use App\UserAccess\Application\Security\AuthTokenIssuer;
use App\UserAccess\Application\Security\RefreshTokenHasherInterface;
use App\UserAccess\Domain\Exception\InvalidRefreshTokenException;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RefreshTokenHandler
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private AuthTokenIssuer $authTokenIssuer,
    ) {
    }

    public function __invoke(RefreshTokenCommand $command): RefreshTokenResult
    {
        $refreshToken = $this->refreshTokenRepository->findByTokenHash(
            $this->refreshTokenHasher->hash($command->refreshToken),
        );

        if ($refreshToken === null || !$refreshToken->isActive(new DateTimeImmutable())) {
            throw InvalidRefreshTokenException::create();
        }

        $tokenPair = $this->authTokenIssuer->rotate($refreshToken);

        return new RefreshTokenResult(
            accessToken: $tokenPair->accessToken,
            refreshToken: $tokenPair->refreshToken,
            tokenType: $tokenPair->tokenType,
            expiresIn: $tokenPair->expiresIn,
        );
    }
}
