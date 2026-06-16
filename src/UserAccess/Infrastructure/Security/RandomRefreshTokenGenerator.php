<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Security;

use App\UserAccess\Application\Security\RefreshTokenGeneratorInterface;

final class RandomRefreshTokenGenerator implements RefreshTokenGeneratorInterface
{
    public function generate(): string
    {
        return bin2hex(random_bytes(64));
    }
}
