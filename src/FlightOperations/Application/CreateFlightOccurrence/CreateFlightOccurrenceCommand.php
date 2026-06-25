<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\CreateFlightOccurrence;

final readonly class CreateFlightOccurrenceCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $operationalDate,
        public int $sequenceNumber = 1,
        public string $source = 'manual',
    ) {
    }
}
