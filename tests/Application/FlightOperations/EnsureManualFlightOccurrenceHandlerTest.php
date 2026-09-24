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
use App\FlightOperations\Domain\Exception\FlightOccurrenceTransitionConflictException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Tests\Application\FlightOperations\Support\FixedOperationalDayProvider;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightDefinitionRepository;
use App\Tests\Application\FlightOperations\Support\InMemoryFlightOccurrenceRepository;
use App\Tests\Support\RecordingEventPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EnsureManualFlightOccurrenceHandlerTest extends TestCase
{
    private const TODAY = '2026-06-25';
    private const YESTERDAY = '2026-06-24';

    public function testCreatesOccurrenceOnTheOperationalDayWhenNoneExists(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $events = new RecordingEventPublisher();
        $handler = $this->handler($definitions, $occurrences, $events);

        $result = $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122()));

        self::assertSame($definition->getId()->toRfc4122(), $result->flightDefinitionId);
        self::assertSame(self::TODAY, $result->operationalDate);
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
        $existing = FlightOccurrence::createManual($definition, new DateTimeImmutable(self::TODAY));
        $occurrences->add($existing);
        $events = new RecordingEventPublisher();
        $handler = $this->handler($definitions, $occurrences, $events);

        $result = $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122()));

        self::assertSame($existing->getId()->toRfc4122(), $result->id);
        self::assertSame(0, $occurrences->saveCalls);
        self::assertSame([], $events->messages);
    }

    public function testRejectsNewRunWhilePreviousDayRunIsInProgress(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $carriedOver = FlightOccurrence::createManual($definition, new DateTimeImmutable(self::YESTERDAY));
        $carriedOver->openCheckIn(Uuid::v4()->toRfc4122(), [['id' => Uuid::v4()->toRfc4122(), 'code' => 'A1']]);
        $occurrences->add($carriedOver);
        $handler = $this->handler($definitions, $occurrences, new RecordingEventPublisher());

        $this->expectException(FlightOccurrenceTransitionConflictException::class);

        try {
            $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122()));
        } finally {
            self::assertSame(0, $occurrences->saveCalls);
        }
    }

    public function testCreatesNewRunWhenPreviousDayRunIsFinished(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $finished = FlightOccurrence::createManual($definition, new DateTimeImmutable(self::YESTERDAY));
        $announcementId = Uuid::v4()->toRfc4122();
        $finished->openCheckIn($announcementId, [['id' => Uuid::v4()->toRfc4122(), 'code' => 'A1']]);
        $finished->closeCheckIn($announcementId);
        $finished->startBoarding($announcementId, ['id' => Uuid::v4()->toRfc4122(), 'code' => 'B2']);
        $occurrences->add($finished);
        $handler = $this->handler($definitions, $occurrences, new RecordingEventPublisher());

        $result = $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122()));

        self::assertSame(self::TODAY, $result->operationalDate);
        self::assertSame(1, $result->sequenceNumber);
    }

    public function testCreatesNewRunWhenPreviousDayRunWasNeverStarted(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definitions->add($definition);
        $occurrences = new InMemoryFlightOccurrenceRepository();
        $occurrences->add(FlightOccurrence::createManual($definition, new DateTimeImmutable(self::YESTERDAY)));
        $handler = $this->handler($definitions, $occurrences, new RecordingEventPublisher());

        $result = $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122()));

        self::assertSame(self::TODAY, $result->operationalDate);
    }

    public function testRejectsUnknownFlightDefinition(): void
    {
        $handler = $this->handler(
            new InMemoryFlightDefinitionRepository(),
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(FlightDefinitionNotFoundException::class);

        $handler(new EnsureManualFlightOccurrenceCommand(Uuid::v4()->toRfc4122()));
    }

    public function testRejectsInactiveFlightDefinition(): void
    {
        $definitions = new InMemoryFlightDefinitionRepository();
        $definition = $this->definition();
        $definition->deactivate();
        $definitions->add($definition);
        $handler = $this->handler(
            $definitions,
            new InMemoryFlightOccurrenceRepository(),
            new RecordingEventPublisher(),
        );

        $this->expectException(InvalidFlightOccurrenceTransitionException::class);

        $handler(new EnsureManualFlightOccurrenceCommand($definition->getId()->toRfc4122()));
    }

    private function handler(
        InMemoryFlightDefinitionRepository $definitions,
        InMemoryFlightOccurrenceRepository $occurrences,
        RecordingEventPublisher $events,
    ): EnsureManualFlightOccurrenceHandler {
        return new EnsureManualFlightOccurrenceHandler(
            $definitions,
            $occurrences,
            new FixedOperationalDayProvider(self::TODAY),
            $events,
        );
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
