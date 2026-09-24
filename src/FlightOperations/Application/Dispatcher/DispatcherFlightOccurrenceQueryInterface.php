<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

use DateTimeImmutable;

interface DispatcherFlightOccurrenceQueryInterface
{
    /** @return list<DispatcherFlightOccurrenceResult> */
    public function search(
        ?string $operationalDate,
        ?string $announcementType,
        ?string $direction,
        bool $includeUnavailable,
    ): array;

    /**
     * The dispatcher board for an operational day (task 027): at most one current
     * occurrence per card — the card's in-progress run of the previous day if any,
     * otherwise its latest run of the day.
     *
     * @return list<DispatcherFlightOccurrenceResult>
     */
    public function board(DateTimeImmutable $operationalDate): array;
}
