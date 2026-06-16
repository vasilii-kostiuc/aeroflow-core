<?php

declare(strict_types=1);
namespace App\UserAccess\Application\RegisterUser;

use App\UserAccess\Application\LoginUser\LoggedInUserResult;

final readonly class RegisterUserResult
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
