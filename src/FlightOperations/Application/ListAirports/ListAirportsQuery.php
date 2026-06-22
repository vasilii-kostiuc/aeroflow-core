<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListAirports;

final readonly class ListAirportsQuery
{
    public function __construct(
        public ?bool $active = null,
        public ?string $search = null,
        public int $page = 1,
        public int $limit = 100,
    ) {
    }
}
