<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess\Support;

use App\UserAccess\Application\Security\PasswordHasherInterface;

final class FakePasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return 'hashed-'.$plainPassword;
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return $hashedPassword === $this->hash($plainPassword);
    }
}
