<?php

declare(strict_types=1);

namespace App\UserAccess\Application\RefreshToken;

final readonly class RefreshTokenResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
    ) {
    }
}
