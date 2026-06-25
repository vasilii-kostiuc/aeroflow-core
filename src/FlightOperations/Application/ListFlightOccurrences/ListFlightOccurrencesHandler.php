<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ListFlightOccurrences;

use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListFlightOccurrencesHandler
{
    public function __construct(private FlightOccurrenceListQueryInterface $query)
    {
    }

    public function __invoke(ListFlightOccurrencesQuery $query): PaginatedResult
    {
        return $this->query->search(
            operationalDate: $query->operationalDate,
            flightDefinitionId: $query->flightDefinitionId,
            direction: $query->direction,
            status: $query->status,
            source: $query->source,
            pagination: Pagination::fromValues($query->page, $query->limit),
        );
    }
}
