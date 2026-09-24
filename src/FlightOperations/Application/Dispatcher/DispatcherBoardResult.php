<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

final readonly class DispatcherBoardResult
{
    public function __construct(
        /** Today's operational day of the airport, Y-m-d. */
        public string $operationalDate,
        /** @var list<DispatcherFlightOccurrenceResult> at most one current occurrence per card */
        public array $occurrences,
    ) {
    }
}
