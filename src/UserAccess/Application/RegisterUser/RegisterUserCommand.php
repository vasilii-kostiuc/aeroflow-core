<?php

declare(strict_types=1);

namespace App\UserAccess\Application\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
