<?php

namespace App\UserAccess\Application\RefreshToken;

final readonly class RefreshTokenCommand
{
    public function __construct(
        public string $refreshToken,
    ) {
    }
}
