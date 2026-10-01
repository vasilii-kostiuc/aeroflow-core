<?php

declare(strict_types=1);

namespace App\UserAccess\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class UserPasswordChanged implements DomainEvent
{
    public function __construct(public string $userId)
    {
    }
}
