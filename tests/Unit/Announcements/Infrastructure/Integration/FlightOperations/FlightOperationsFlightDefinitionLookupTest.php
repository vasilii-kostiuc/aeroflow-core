<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Infrastructure\Integration\FlightOperations;

use App\Announcements\Domain\Enum\FlightDirection as AnnouncementFlightDirection;
use App\Announcements\Infrastructure\Integration\FlightOperations\FlightOperationsFlightDefinitionLookup;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FlightOperationsFlightDefinitionLookupTest extends TestCase
{
    public function testMapsFlightDefinitionToAnnouncementsSnapshot(): void
    {
        $flightDefinition = FlightDefinition::create(
            FlightNumber::fromString('AF123'),
            FlightDirection::Arrival,
            AirportCode::fromString('FCO'),
            AirportCode::fromString('RMO'),
        );
        $flightDefinition->deactivate();

        $repository = $this->createMock(FlightDefinitionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findById')
            ->with($flightDefinition->getId())
            ->willReturn($flightDefinition);

        $snapshot = new FlightOperationsFlightDefinitionLookup($repository)
            ->findById($flightDefinition->getId());

        self::assertNotNull($snapshot);
        self::assertFalse($snapshot->active);
        self::assertSame(AnnouncementFlightDirection::Arrival, $snapshot->direction);
    }

    public function testReturnsNullForUnknownFlightDefinition(): void
    {
        $id = Uuid::v7();
        $repository = $this->createMock(FlightDefinitionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        self::assertNull(new FlightOperationsFlightDefinitionLookup($repository)->findById($id));
    }
}
