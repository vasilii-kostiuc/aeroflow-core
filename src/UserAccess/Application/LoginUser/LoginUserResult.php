<?php

declare(strict_types=1);

namespace App\UserAccess\Application\LoginUser;

final readonly class LoginUserResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
        public LoggedInUserResult $user,
    ) {
    }
}
