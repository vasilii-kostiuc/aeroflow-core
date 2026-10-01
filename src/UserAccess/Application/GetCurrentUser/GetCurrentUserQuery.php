<?php

declare(strict_types=1);

namespace App\UserAccess\Application\GetCurrentUser;

final readonly class GetCurrentUserQuery
{
    public function __construct(public string $email)
    {
    }
}
