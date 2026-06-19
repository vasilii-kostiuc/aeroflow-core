<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations\Support;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Application\ListFlightDefinitions\FlightDefinitionListQueryInterface;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use App\Shared\Application\Pagination\PaginationMetadata;

final class InMemoryFlightDefinitionListQuery implements FlightDefinitionListQueryInterface
{
    /**
     * @var array{?bool, ?FlightDirection, ?string, Pagination}|null
     */
    public ?array $lastArguments = null;

    /**
     * @return PaginatedResult<FlightDefinitionResult>
     */
    public function search(
        ?bool $active,
        ?FlightDirection $direction,
        ?string $search,
        Pagination $pagination,
    ): PaginatedResult {
        $this->lastArguments = [$active, $direction, $search, $pagination];

        return new PaginatedResult(
            [new FlightDefinitionResult(
                '01900000-0000-7000-8000-000000000001',
                '5F123',
                'departure',
                'KIV',
                'FCO',
                true,
                '2026-06-18T12:00:00+00:00',
                '2026-06-18T12:00:00+00:00',
            )],
            new PaginationMetadata($pagination->page, $pagination->limit, 1, 1),
        );
    }
}
