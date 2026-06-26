<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EnsureManualFlightOccurrence;

final readonly class EnsureManualFlightOccurrenceCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $operationalDate,
        public int $sequenceNumber = 1,
    ) {
    }
}
