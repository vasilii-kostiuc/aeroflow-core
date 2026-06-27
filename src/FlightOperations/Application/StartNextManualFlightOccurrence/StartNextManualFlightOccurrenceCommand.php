<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\StartNextManualFlightOccurrence;

final readonly class StartNextManualFlightOccurrenceCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $operationalDate,
    ) {
    }
}
