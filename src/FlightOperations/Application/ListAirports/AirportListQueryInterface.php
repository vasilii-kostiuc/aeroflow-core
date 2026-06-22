<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListAirports;

use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;

interface AirportListQueryInterface
{
    /**
     * @return PaginatedResult<\App\FlightOperations\Application\AirportResult>
     */
    public function search(?bool $active, ?string $search, Pagination $pagination): PaginatedResult;
}
