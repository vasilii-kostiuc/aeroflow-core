<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

interface DispatcherFlightOccurrenceQueryInterface
{
    /** @return list<DispatcherFlightOccurrenceResult> */
    public function search(
        ?string $operationalDate,
        ?string $announcementType,
        ?string $direction,
        bool $includeUnavailable,
    ): array;
}
