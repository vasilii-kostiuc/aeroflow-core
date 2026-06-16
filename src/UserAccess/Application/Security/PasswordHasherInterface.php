<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Security;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(
        string $plainPassword,
        string $hashedPassword
    ): bool;
}
