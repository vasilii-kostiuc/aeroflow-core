<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations;

use App\FlightOperations\Application\CreateFlightOccurrence\CreateFlightOccurrenceCommand;
use App\FlightOperations\Application\CreateFlightOccurrence\CreateFlightOccurrenceHandler;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Event\FlightOccurrenceCreated;
use App\FlightOperations\Domain\Exception\DuplicateFlightOccurrenceException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightOccurrenceRepository;
use App\Tests\Support\RecordingEventPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateFlightOccurrenceHandlerTest extends TestCase
{
    public function testCreatesManualOccurrence(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $events = new RecordingEventPublisher();
        $handler = new CreateFlightOccurrenceHandler($definitions, $occurrences, $events);

        $result = $handler(new CreateFlightOccurrenceCommand(
            $definition->getId()->toRfc4122(),
            '2026-06-25',
        ));

        self::assertSame($definition->getId()->toRfc4122(), $result->flightDefinitionId);
        self::assertSame('scheduled', $result->status);
        self::assertSame(1, $occurrences->saveCalls);
        self::assertInstanceOf(FlightOccurrenceCreated::class, $events->messages[0]);
    }

    public function testRejectsDuplicateBusinessKey(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $occurrences->add(\App\FlightOperations\Domain\Entity\FlightOccurrence::createManual($definition, new DateTimeImmutable('2026-06-25')));
        $handler = new CreateFlightOccurrenceHandler($definitions, $occurrences, new RecordingEventPublisher());

        $this->expectException(DuplicateFlightOccurrenceException::class);

        $handler(new CreateFlightOccurrenceCommand($definition->getId()->toRfc4122(), '2026-06-25'));
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
