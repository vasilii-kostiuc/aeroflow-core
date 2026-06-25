<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\FlightOccurrenceCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnFlightOccurrenceCreated
{
    public function __invoke(FlightOccurrenceCreated $event): void
    {
        // TODO: audit log.
    }
}
