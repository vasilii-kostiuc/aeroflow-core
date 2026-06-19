<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\ListFlightDefinitions\ListFlightDefinitionsHandler;
use App\FlightOperations\Application\ListFlightDefinitions\ListFlightDefinitionsQuery;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\Shared\Application\Pagination\Exception\InvalidPaginationException;
use App\Shared\Application\Pagination\Pagination;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionListQuery;
use PHPUnit\Framework\TestCase;

final class ListFlightDefinitionsHandlerTest extends TestCase
{
    public function testNormalizesFiltersAndDelegatesPagination(): void
    {
        $queryService = new InMemoryFlightDefinitionListQuery();
        $handler = new ListFlightDefinitionsHandler($queryService);

        $result = $handler(new ListFlightDefinitionsQuery(true, 'DEPARTURE', ' 5f ', 2, 10));

        self::assertSame(true, $queryService->lastArguments[0]);
        self::assertSame(FlightDirection::Departure, $queryService->lastArguments[1]);
        self::assertSame('5F', $queryService->lastArguments[2]);
        self::assertEquals(Pagination::fromValues(2, 10), $queryService->lastArguments[3]);
        self::assertSame(2, $result->pagination->page);
    }

    public function testRejectsInvalidPage(): void
    {
        $handler = new ListFlightDefinitionsHandler(new InMemoryFlightDefinitionListQuery());

        $this->expectException(InvalidPaginationException::class);

        $handler(new ListFlightDefinitionsQuery(page: 0));
    }

    public function testRejectsInvalidLimit(): void
    {
        $handler = new ListFlightDefinitionsHandler(new InMemoryFlightDefinitionListQuery());

        $this->expectException(InvalidPaginationException::class);

        $handler(new ListFlightDefinitionsQuery(limit: 101));
    }
}
