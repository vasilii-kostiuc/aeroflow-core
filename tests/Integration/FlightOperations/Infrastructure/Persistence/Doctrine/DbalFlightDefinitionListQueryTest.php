<?php

declare(strict_types=1);

namespace App\Tests\Integration\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Application\ListFlightDefinitions\FlightDefinitionListQueryInterface;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Shared\Application\Pagination\Pagination;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DbalFlightDefinitionListQueryTest extends KernelTestCase
{
    public function testFiltersAndPaginatesDefinitions(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(FlightDefinitionRepositoryInterface::class);
        $listQuery = self::getContainer()->get(FlightDefinitionListQueryInterface::class);
        self::assertInstanceOf(FlightDefinitionRepositoryInterface::class, $repository);
        self::assertInstanceOf(FlightDefinitionListQueryInterface::class, $listQuery);
        $suffix = (string) random_int(100, 999);

        $first = $this->definition('AF'.$suffix, FlightDirection::Departure, 'KIV', 'FCO');
        $second = $this->definition('WZ'.$suffix, FlightDirection::Departure, 'KIV', 'FCO');
        $inactive = $this->definition('LH'.$suffix, FlightDirection::Arrival, 'FCO', 'KIV');
        $inactive->deactivate();
        $repository->save($first);
        $repository->save($second);
        $repository->save($inactive);

        $page = $listQuery->search(
            true,
            FlightDirection::Departure,
            $suffix,
            Pagination::fromValues(1, 1),
        );

        self::assertCount(1, $page->items);
        self::assertSame(2, $page->pagination->totalItems);
        self::assertSame(2, $page->pagination->totalPages);
        self::assertSame('AF'.$suffix, $page->items[0]->flightNumber);

        $inactivePage = $listQuery->search(
            false,
            FlightDirection::Arrival,
            $suffix,
            Pagination::fromValues(1, 20),
        );
        self::assertSame(1, $inactivePage->pagination->totalItems);
        self::assertFalse($inactivePage->items[0]->active);
    }

    private function definition(
        string $flightNumber,
        FlightDirection $direction,
        string $origin,
        string $destination,
    ): FlightDefinition {
        $definition = FlightDefinition::create(
            FlightNumber::fromString($flightNumber),
            $direction,
            AirportCode::fromString($origin),
            AirportCode::fromString($destination),
        );
        $definition->pullEvents();

        return $definition;
    }
}
