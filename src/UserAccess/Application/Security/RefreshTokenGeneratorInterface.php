<?php

namespace App\UserAccess\Application\Security;

interface RefreshTokenGeneratorInterface
{
    public function generate(): string;
}
