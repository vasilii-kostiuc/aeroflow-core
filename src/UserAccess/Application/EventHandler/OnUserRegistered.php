<?php

declare(strict_types=1);

namespace App\UserAccess\Application\EventHandler;

use App\UserAccess\Domain\Event\UserRegistered;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnUserRegistered
{
    public function __invoke(UserRegistered $event): void
    {
        // TODO: send welcome email, audit log, etc.
    }
}
