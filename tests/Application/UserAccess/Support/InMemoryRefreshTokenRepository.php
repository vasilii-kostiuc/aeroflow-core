<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess\Support;

use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Repository\RefreshTokenRepositoryInterface;

final class InMemoryRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var array<string, RefreshToken>
     */
    private array $refreshTokens = [];

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        return $this->refreshTokens[$tokenHash] ?? null;
    }

    public function saveAll(array $refreshTokens): void
    {
        foreach ($refreshTokens as $refreshToken) {
            $this->refreshTokens[$refreshToken->getTokenHash()] = $refreshToken;
        }
    }

    /**
     * @return array<string, RefreshToken>
     */
    public function all(): array
    {
        return $this->refreshTokens;
    }
}
