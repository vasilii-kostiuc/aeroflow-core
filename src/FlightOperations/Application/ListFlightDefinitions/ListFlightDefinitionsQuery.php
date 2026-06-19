<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListFlightDefinitions;

final readonly class ListFlightDefinitionsQuery
{
    public function __construct(
        public ?bool $active = null,
        public ?string $direction = null,
        public ?string $search = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
