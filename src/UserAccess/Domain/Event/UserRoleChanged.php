<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class UserRoleChanged implements DomainEvent
{
    public function __construct(
        public string $userId,
        /** `ROLE_ADMIN`, `ROLE_DISPATCHER`, or null for no access. */
        public ?string $role,
    ) {
    }
}
