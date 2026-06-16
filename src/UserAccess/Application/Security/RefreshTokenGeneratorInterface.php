<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Security;

interface RefreshTokenGeneratorInterface
{
    public function generate(): string;
}
