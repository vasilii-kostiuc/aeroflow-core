<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Security;

interface RefreshTokenHasherInterface
{
    public function hash(string $plainToken): string;
}
