<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess\Support;

use App\UserAccess\Application\Security\AccessTokenIssuerInterface;
use App\UserAccess\Domain\Entity\User;

final class FakeAccessTokenIssuer implements AccessTokenIssuerInterface
{
    public function issue(User $user): string
    {
        return 'access-token-for-'.$user->getEmail();
    }

    public function expiresIn(): int
    {
        return 3600;
    }
}
