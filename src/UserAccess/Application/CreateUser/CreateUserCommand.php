<?php

declare(strict_types=1);

namespace App\UserAccess\Application\CreateUser;

/** An administrator (or the console) creates an account; there is no self-registration. */
final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        /** `admin`, `dispatcher`, or null for no access. */
        public ?string $role,
    ) {
    }
}
