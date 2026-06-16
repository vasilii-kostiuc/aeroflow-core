<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Security;

use App\UserAccess\Application\Security\RefreshTokenHasherInterface;

final class Sha256RefreshTokenHasher implements RefreshTokenHasherInterface
{
    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
