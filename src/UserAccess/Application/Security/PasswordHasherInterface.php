<?php
namespace App\UserAccess\Application\Security;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(
        string $plainPassword,
        string $hashedPassword
    ): bool;
}