<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListFlightOccurrences;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;

interface FlightOccurrenceListQueryInterface
{
    /** @return PaginatedResult<FlightOccurrenceResult> */
    public function search(
        ?string $operationalDate,
        ?string $flightDefinitionId,
        ?string $direction,
        ?string $status,
        ?string $source,
        Pagination $pagination,
    ): PaginatedResult;
}
