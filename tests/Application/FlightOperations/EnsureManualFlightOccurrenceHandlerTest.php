<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\EnsureManualFlightOccurrence\EnsureManualFlightOccurrenceCommand;
use App\FlightOperations\Application\EnsureManualFlightOccurrence\EnsureManualFlightOccurrenceHandler;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Event\FlightOccurrenceCreated;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightOccurrenceRepository;
use App\Tests\Support\RecordingEventPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EnsureManualFlightOccurrenceHandlerTest extends TestCase
{
    public function testCreatesOccurrenceWhenNoneExists(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $events = new RecordingEventPublisher();
        $handler = new EnsureManualFlightOccurrenceHandler($definitions, $occurrences, $events);

        $result = $handler(new EnsureManualFlightOccurrenceCommand(
            $definition->getId()->toRfc4122(),
            '2026-06-25',
        ));

        self::assertSame($definition->getId()->toRfc4122(), $result->flightDefinitionId);
        self::assertSame('scheduled', $result->status);
        self::assertSame(1, $occurrences->saveCalls);
        self::assertInstanceOf(FlightOccurrenceCreated::class, $events->messages[0]);
    }

    public function testReturnsExistingOccurrenceForSameBusinessKey(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $existing = FlightOccurrence::createManual($definition, new DateTimeImmutable('2026-06-25'));
        $occurrences->add($existing);
        $events = new RecordingEventPublisher();
        $handler = new EnsureManualFlightOccurrenceHandler($definitions, $occurrences, $events);

        $result = $handler(new EnsureManualFlightOccurrenceCommand(
            $definition->getId()->toRfc4122(),
            '2026-06-25',
        ));

        self::assertSame($existing->getId()->toRfc4122(), $result->id);
        self::assertSame(0, $occurrences->saveCalls);
        self::assertSame([], $events->messages);
    }

    public function testRejectsUnknownFlightDefinition(): void
    {
        $handler = new EnsureManualFlightOccurrenceHandler(
            new InMemoryFlightDefinitionRepository(),
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(FlightDefinitionNotFoundException::class);

        $handler(new EnsureManualFlightOccurrenceCommand(Uuid::v4()->toRfc4122(), '2026-06-25'));
    }

    public function testRejectsInactiveFlightDefinition(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definition->deactivate();
        $definitions->add($definition);
        $handler = new EnsureManualFlightOccurrenceHandler(
            $definitions,
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(InvalidFlightOccurrenceTransitionException::class);

        $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122(), '2026-06-25'));
    }

    private function definition(): FlightDefinition
    {
        return FlightDefinition::create(
            FlightNumber::fromString('5F123'),
            FlightDirection::Departure,
            AirportCode::fromString('KIV'),
            AirportCode::fromString('FCO'),
        );
    }
}
