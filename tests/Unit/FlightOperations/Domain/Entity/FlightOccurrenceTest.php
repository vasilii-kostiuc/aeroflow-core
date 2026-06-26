<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Enum\FlightOccurrenceStatus;
use App\FlightOperations\Domain\Event\BoardingStarted;
use App\FlightOperations\Domain\Event\CheckInClosed;
use App\FlightOperations\Domain\Event\CheckInOpened;
use App\FlightOperations\Domain\Event\FlightOccurrenceCreated;
use App\FlightOperations\Domain\Exception\FlightOccurrenceTransitionConflictException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FlightOccurrenceTest extends TestCase
{
    public function testCreatesScheduledManualOccurrenceWithSnapshots(): void
    {
        $occurrence = FlightOccurrence::createManual($this->departureDefinition(), new DateTimeImmutable('2026-06-25'));

        self::assertSame(FlightOccurrenceStatus::Scheduled, $occurrence->getStatus());
        self::assertSame('5F123', $occurrence->getFlightNumberSnapshot());
        self::assertSame('2026-06-25', $occurrence->getOperationalDate()->format('Y-m-d'));
        self::assertInstanceOf(FlightOccurrenceCreated::class, $occurrence->pullEvents()[0]);
    }

    public function testDepartureLifecycleStoresResourceSnapshots(): void
    {
        $occurrence = FlightOccurrence::createManual($this->departureDefinition(), new DateTimeImmutable('2026-06-25'));
        $occurrence->pullEvents();
        $openAnnouncementId = Uuid::v7()->toRfc4122();
        $closeAnnouncementId = Uuid::v7()->toRfc4122();
        $boardingAnnouncementId = Uuid::v7()->toRfc4122();

        $occurrence->openCheckIn($openAnnouncementId, [['id' => Uuid::v7()->toRfc4122(), 'code' => '1']]);
        self::assertSame(FlightOccurrenceStatus::CheckInOpen, $occurrence->getStatus());
        self::assertSame('1', $occurrence->getCheckInCounters()[0]['code']);

        $occurrence->closeCheckIn($closeAnnouncementId);
        self::assertSame(FlightOccurrenceStatus::CheckInClosed, $occurrence->getStatus());

        $occurrence->startBoarding($boardingAnnouncementId, ['id' => Uuid::v7()->toRfc4122(), 'code' => 'A5']);
        self::assertSame(FlightOccurrenceStatus::Boarding, $occurrence->getStatus());
        self::assertSame('A5', $occurrence->getGate()['code']);

        $events = $occurrence->pullEvents();
        self::assertInstanceOf(CheckInOpened::class, $events[0]);
        self::assertInstanceOf(CheckInClosed::class, $events[1]);
        self::assertInstanceOf(BoardingStarted::class, $events[2]);
    }

    public function testRejectsInvalidTransitions(): void
    {
        $occurrence = FlightOccurrence::createManual($this->departureDefinition(), new DateTimeImmutable('2026-06-25'));

        $this->expectException(FlightOccurrenceTransitionConflictException::class);

        $occurrence->closeCheckIn(Uuid::v7()->toRfc4122());
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
}
