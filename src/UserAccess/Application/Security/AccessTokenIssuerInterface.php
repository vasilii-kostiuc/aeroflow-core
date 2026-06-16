<?php

declare(strict_types=1);

namespace App\UserAccess\Application\Security;

use App\UserAccess\Domain\Entity\User;

interface AccessTokenIssuerInterface
{
    public function issue(User $user): string;

    public function expiresIn(): int;
}
