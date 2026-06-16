<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Repository;

use App\UserAccess\Domain\Entity\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function findByTokenHash(string $tokenHash): ?RefreshToken;

    /**
     * @param list<RefreshToken> $refreshTokens
     */
    public function saveAll(array $refreshTokens): void;
}
