<?php

declare(strict_types=1);

namespace App\UserAccess\Application\LoginUser;

final readonly class LoginUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
