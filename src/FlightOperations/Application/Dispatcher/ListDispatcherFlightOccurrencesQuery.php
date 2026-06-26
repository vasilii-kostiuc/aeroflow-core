<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

final readonly class ListDispatcherFlightOccurrencesQuery
{
    public function __construct(
        public ?string $operationalDate = null,
        public ?string $announcementType = null,
        public ?string $direction = null,
        public bool $includeUnavailable = false,
    ) {
    }
}
