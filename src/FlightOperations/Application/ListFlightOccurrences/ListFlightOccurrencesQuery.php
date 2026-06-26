<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListFlightOccurrences;

final readonly class ListFlightOccurrencesQuery
{
    public function __construct(
        public ?string $operationalDate = null,
        public ?string $flightDefinitionId = null,
        public ?string $direction = null,
        public ?string $status = null,
        public ?string $source = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
