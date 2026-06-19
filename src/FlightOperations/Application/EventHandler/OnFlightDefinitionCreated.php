<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\FlightDefinitionCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnFlightDefinitionCreated
{
    public function __invoke(FlightDefinitionCreated $event): void
    {
        // TODO: audit log.
    }
}
