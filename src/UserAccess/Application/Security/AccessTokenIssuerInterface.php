<?php

namespace App\UserAccess\Application\Security;

use App\UserAccess\Domain\Entity\User;

interface AccessTokenIssuerInterface
{
    public function issue(User $user): string;

    public function expiresIn(): int;
}
