<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\CheckInClosed;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnCheckInClosed
{
    public function __invoke(CheckInClosed $event): void
    {
        // TODO: audit log.
    }
}
