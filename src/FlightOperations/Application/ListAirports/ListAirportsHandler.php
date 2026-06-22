<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListAirports;

use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListAirportsHandler
{
    public function __construct(private AirportListQueryInterface $listQuery)
    {
    }

    /**
     * @return PaginatedResult<\App\FlightOperations\Application\AirportResult>
     */
    public function __invoke(ListAirportsQuery $query): PaginatedResult
    {
        $search = $query->search === null ? null : trim($query->search);

        return $this->listQuery->search(
            $query->active,
            $search === '' ? null : $search,
            Pagination::fromValues($query->page, $query->limit),
        );
    }
}
