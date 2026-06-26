<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\CheckInOpened;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnCheckInOpened
{
    public function __invoke(CheckInOpened $event): void
    {
        // TODO: audit log.
    }
}
