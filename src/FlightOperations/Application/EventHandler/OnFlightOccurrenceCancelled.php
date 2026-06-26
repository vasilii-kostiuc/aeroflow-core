<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\FlightOccurrenceCancelled;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnFlightOccurrenceCancelled
{
    public function __invoke(FlightOccurrenceCancelled $event): void
    {
        // TODO: audit log.
    }
}
