<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListFlightDefinitions;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;

interface FlightDefinitionListQueryInterface
{
    /**
     * @return PaginatedResult<FlightDefinitionResult>
     */
    public function search(
        ?bool $active,
        ?FlightDirection $direction,
        ?string $search,
        Pagination $pagination,
    ): PaginatedResult;
}
