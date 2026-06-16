<?php

namespace App\UserAccess\Application\Security;

interface RefreshTokenHasherInterface
{
    public function hash(string $plainToken): string;
}
