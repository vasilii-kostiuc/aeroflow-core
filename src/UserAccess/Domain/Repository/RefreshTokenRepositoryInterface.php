<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Repository;

use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;

interface RefreshTokenRepositoryInterface
{
    public function findByTokenHash(string $tokenHash): ?RefreshToken;

    /**
     * @param list<RefreshToken> $refreshTokens
     */
    public function saveAll(array $refreshTokens): void;

    /**
     * Tokens of the user that are not revoked yet (expired ones included — revoking
     * them is harmless).
     *
     * @return list<RefreshToken>
     */
    public function findNotRevokedByUser(User $user): array;
}
