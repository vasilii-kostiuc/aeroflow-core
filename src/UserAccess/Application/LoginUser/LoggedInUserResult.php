<?php

declare(strict_types=1);

namespace App\UserAccess\Application\LoginUser;

final readonly class LoggedInUserResult
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public array $roles,
    ) {
    }
}
