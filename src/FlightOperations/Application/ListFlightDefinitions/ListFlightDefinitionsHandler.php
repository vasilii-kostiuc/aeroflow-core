<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListFlightDefinitions;

use App\FlightOperations\Domain\Enum\FlightDirection;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListFlightDefinitionsHandler
{
    public function __construct(
        private FlightDefinitionListQueryInterface $listQuery,
    ) {
    }

    /**
     * @return PaginatedResult<\App\FlightOperations\Application\FlightDefinitionResult>
     */
    public function __invoke(ListFlightDefinitionsQuery $query): PaginatedResult
    {
        $pagination = Pagination::fromValues($query->page, $query->limit);
        $direction = $query->direction === null ? null : FlightDirection::fromString($query->direction);
        $search = $query->search === null ? null : strtoupper(trim($query->search));
        $search = $search === '' ? null : $search;

        return $this->listQuery->search($query->active, $direction, $search, $pagination);
    }
}
