<?php

declare(strict_types=1);

namespace App\UserAccess\Application\ChangeUserRole;

final readonly class ChangeUserRoleCommand
{
    public function __construct(
        public string $userId,
        /** `admin`, `dispatcher`, or null to remove access. */
        public ?string $role,
    ) {
    }
}
