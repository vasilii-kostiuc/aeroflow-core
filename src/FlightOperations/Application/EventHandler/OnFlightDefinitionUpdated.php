<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\FlightDefinitionUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnFlightDefinitionUpdated
{
    public function __invoke(FlightDefinitionUpdated $event): void
    {
        // TODO: audit log.
    }
}
