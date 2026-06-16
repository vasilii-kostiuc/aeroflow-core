<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class UserLoggedOut implements DomainEvent
{
    public function __construct(
        public string $userId,
        public string $email,
    ) {
    }
}
