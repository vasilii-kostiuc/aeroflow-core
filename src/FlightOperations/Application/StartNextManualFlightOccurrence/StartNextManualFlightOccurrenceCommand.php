<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\StartNextManualFlightOccurrence;

/**
 * The operational date is not part of the command: the server decides it from the
 * airport clock (task 027).
 */
final readonly class StartNextManualFlightOccurrenceCommand
{
    public function __construct(
        public string $flightDefinitionId,
    ) {
    }
}
