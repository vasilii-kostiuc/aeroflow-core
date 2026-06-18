<?php

declare(strict_types=1);

namespace App\UserAccess\Application\EventHandler;

use App\UserAccess\Domain\Event\UserLoggedIn;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnUserLoggedIn
{
    public function __invoke(UserLoggedIn $event): void
    {
        // TODO: audit log.
    }
}
