<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\FlightOccurrenceCompleted;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnFlightOccurrenceCompleted
{
    public function __invoke(FlightOccurrenceCompleted $event): void
    {
        // TODO: audit log.
    }
}
