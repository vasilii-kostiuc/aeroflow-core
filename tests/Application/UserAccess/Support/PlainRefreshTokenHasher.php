<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess\Support;

use App\UserAccess\Application\Security\RefreshTokenHasherInterface;

final class PlainRefreshTokenHasher implements RefreshTokenHasherInterface
{
    public function hash(string $plainToken): string
    {
        return 'hash-'.$plainToken;
    }
}
