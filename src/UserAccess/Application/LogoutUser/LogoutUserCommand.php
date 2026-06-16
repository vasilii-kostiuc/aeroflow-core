<?php

declare(strict_types=1);

namespace App\UserAccess\Application\LogoutUser;

final readonly class LogoutUserCommand
{
    public function __construct(
        public string $refreshToken,
    ) {
    }
}
