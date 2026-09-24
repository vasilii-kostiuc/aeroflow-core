<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EnsureManualFlightOccurrence;

/**
 * The operational date is not part of the command: the server decides it from the
 * airport clock (task 027).
 */
final readonly class EnsureManualFlightOccurrenceCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public int $sequenceNumber = 1,
    ) {
    }
}
