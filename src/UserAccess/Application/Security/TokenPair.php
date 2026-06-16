<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Security;

final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public string $tokenType = 'Bearer',
    ) {
    }
}
