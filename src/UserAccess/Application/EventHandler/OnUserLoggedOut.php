<?php

declare(strict_types=1);

namespace App\UserAccess\Application\EventHandler;

use App\UserAccess\Domain\Event\UserLoggedOut;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnUserLoggedOut
{
    public function __invoke(UserLoggedOut $event): void
    {
        // TODO: audit log.
    }
}
