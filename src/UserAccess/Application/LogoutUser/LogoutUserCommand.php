<?php

namespace App\UserAccess\Application\LogoutUser;

final readonly class LogoutUserCommand
{
    public function __construct(
        public string $refreshToken,
    ) {
    }
}
