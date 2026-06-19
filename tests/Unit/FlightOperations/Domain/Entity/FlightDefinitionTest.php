<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Event\FlightDefinitionActivated;
use App\FlightOperations\Domain\Event\FlightDefinitionCreated;
use App\FlightOperations\Domain\Event\FlightDefinitionDeactivated;
use App\FlightOperations\Domain\Event\FlightDefinitionUpdated;
use App\FlightOperations\Domain\Exception\InvalidFlightRouteException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use PHPUnit\Framework\TestCase;

final class FlightDefinitionTest extends TestCase
{
    public function testCreatesActiveDefinitionAndPublishesCreatedEvent(): void
    {
        $definition = $this->createDefinition();

        self::assertTrue($definition->isActive());
        self::assertSame('5F123', $definition->getFlightNumber()->toString());
        self::assertSame(FlightDirection::Departure, $definition->getDirection());

        $events = $definition->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(FlightDefinitionCreated::class, $events[0]);
        self::assertSame($definition->getId()->toRfc4122(), $events[0]->flightDefinitionId);
    }

    public function testRejectsSameOriginAndDestination(): void
    {
        $this->expectException(InvalidFlightRouteException::class);

        FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('KIV'),
        );
    }

    public function testUpdatesDetailsOnlyWhenTheyChange(): void
    {
        $definition = $this->createDefinition();
        $definition->pullEvents();

        self::assertFalse($definition->updateDetails(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        ));
        self::assertSame([], $definition->pullEvents());

        self::assertTrue($definition->updateDetails(
            FlightNumber::fromString('WZZ42'),
            FlightDirection::Arrival,
            AirportCode::fromString('FCO'),
            AirportCode::fromString('KIV'),
        ));
        self::assertSame('WZZ42', $definition->getFlightNumber()->toString());
        self::assertInstanceOf(FlightDefinitionUpdated::class, $definition->pullEvents()[0]);
    }

    public function testActivationChangesAreIdempotent(): void
    {
        $definition = $this->createDefinition();
        $definition->pullEvents();

        self::assertTrue($definition->deactivate());
        self::assertFalse($definition->deactivate());
        self::assertInstanceOf(FlightDefinitionDeactivated::class, $definition->pullEvents()[0]);

        self::assertTrue($definition->activate());
        self::assertFalse($definition->activate());
        self::assertInstanceOf(FlightDefinitionActivated::class, $definition->pullEvents()[0]);
    }

    private function createDefinition(): FlightDefinition
    {
        return FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
    }
}
