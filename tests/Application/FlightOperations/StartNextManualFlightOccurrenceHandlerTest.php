<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\StartNextManualFlightOccurrence\StartNextManualFlightOccurrenceCommand;
use App\FlightOperations\Application\StartNextManualFlightOccurrence\StartNextManualFlightOccurrenceHandler;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Event\FlightOccurrenceCreated;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Exception\FlightOccurrenceTransitionConflictException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightOccurrenceRepository;
use App\Tests\Support\RecordingEventPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class StartNextManualFlightOccurrenceHandlerTest extends TestCase
{
    private const DATE = '2026-06-25';

    public function testCreatesNextRunWhenPreviousReachedFinalDepartureStatus(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->departureDefinition();
        $definitions->add($definition);

        $occurrences = new InMemoryFlightOccurrenceRepository();
        $occurrences->add($this->boardingOccurrence($definition));
        $events = new RecordingEventPublisher();
        $handler = new StartNextManualFlightOccurrenceHandler($definitions, $occurrences, $events);

        $result = $handler(new StartNextManualFlightOccurrenceCommand($definition->getId()->toRfc4122(), self::DATE));

        self::assertSame(2, $result->sequenceNumber);
        self::assertSame('scheduled', $result->status);
        self::assertSame(1, $occurrences->saveCalls);
        self::assertInstanceOf(FlightOccurrenceCreated::class, $events->messages[0]);
    }

    public function testCreatesNextRunWhenPreviousReachedFinalArrivalStatus(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->arrivalDefinition();
        $definitions->add($definition);

        $occurrences = new InMemoryFlightOccurrenceRepository();
        $arrival = FlightOccurrence::createManual($definition, new DateTimeImmutable(self::DATE));
        $arrival->announceArrival(Uuid::v4()->toRfc4122());
        $occurrences->add($arrival);
        $handler = new StartNextManualFlightOccurrenceHandler($definitions, $occurrences, new RecordingEventPublisher());

        $result = $handler(new StartNextManualFlightOccurrenceCommand($definition->getId()->toRfc4122(), self::DATE));

        self::assertSame(2, $result->sequenceNumber);
        self::assertSame('scheduled', $result->status);
    }

    public function testRejectsWhenPreviousRunNotFinished(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->departureDefinition();
        $definitions->add($definition);

        $occurrences = new InMemoryFlightOccurrenceRepository();
        $open = FlightOccurrence::createManual($definition, new DateTimeImmutable(self::DATE));
        $open->openCheckIn(Uuid::v4()->toRfc4122(), [['id' => Uuid::v4()->toRfc4122(), 'code' => 'A1']]);
        $occurrences->add($open);
        $handler = new StartNextManualFlightOccurrenceHandler($definitions, $occurrences, new RecordingEventPublisher());

        $this->expectException(FlightOccurrenceTransitionConflictException::class);

        try {
            $handler(new StartNextManualFlightOccurrenceCommand($definition->getId()->toRfc4122(), self::DATE));
        } finally {
            self::assertSame(0, $occurrences->saveCalls);
        }
    }

    public function testRejectsWhenNoPreviousRunExists(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->departureDefinition();
        $definitions->add($definition);
        $handler = new StartNextManualFlightOccurrenceHandler(
            $definitions,
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(FlightOccurrenceTransitionConflictException::class);

        $handler(new StartNextManualFlightOccurrenceCommand($definition->getId()->toRfc4122(), self::DATE));
    }

    public function testRejectsUnknownFlightDefinition(): void
    {
        $handler = new StartNextManualFlightOccurrenceHandler(
            new InMemoryFlightDefinitionRepository(),
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(FlightDefinitionNotFoundException::class);

        $handler(new StartNextManualFlightOccurrenceCommand(Uuid::v4()->toRfc4122(), self::DATE));
    }

    public function testRejectsInactiveFlightDefinition(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->departureDefinition();
        $definition->deactivate();
        $definitions->add($definition);
        $handler = new StartNextManualFlightOccurrenceHandler(
            $definitions,
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(InvalidFlightOccurrenceTransitionException::class);

        $handler(new StartNextManualFlightOccurrenceCommand($definition->getId()->toRfc4122(), self::DATE));
    }

    private function boardingOccurrence(FlightDefinition $definition): FlightOccurrence
    {
        $occurrence = FlightOccurrence::createManual($definition, new DateTimeImmutable(self::DATE));
        $announcementId = Uuid::v4()->toRfc4122();
        $occurrence->openCheckIn($announcementId, [['id' => Uuid::v4()->toRfc4122(), 'code' => 'A1']]);
        $occurrence->closeCheckIn($announcementId);
        $occurrence->startBoarding($announcementId, ['id' => Uuid::v4()->toRfc4122(), 'code' => 'B2']);

        return $occurrence;
    }

    private function departureDefinition(): FlightDefinition
    {
        return FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
    }

    private function arrivalDefinition(): FlightDefinition
    {
        return FlightDefinition::create(
            FlightNumber::fromString('5F124'),
            FlightDirection::Arrival,
            AirportCode::fromString('FCO'),
            AirportCode::fromString('KIV'),
        );
    }
}
