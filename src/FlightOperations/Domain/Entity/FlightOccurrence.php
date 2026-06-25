<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Enum\FlightOccurrenceSource;
use App\FlightOperations\Domain\Enum\FlightOccurrenceStatus;
use App\FlightOperations\Domain\Event\ArrivalAnnounced;
use App\FlightOperations\Domain\Event\BoardingStarted;
use App\FlightOperations\Domain\Event\CheckInClosed;
use App\FlightOperations\Domain\Event\CheckInContinued;
use App\FlightOperations\Domain\Event\CheckInOpened;
use App\FlightOperations\Domain\Event\FlightOccurrenceCancelled;
use App\FlightOperations\Domain\Event\FlightOccurrenceCompleted;
use App\FlightOperations\Domain\Event\FlightOccurrenceCreated;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'flight_occurrence')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_FLIGHT_OCCURRENCE_BUSINESS_KEY',
    columns: ['flight_definition_id', 'operational_date', 'source', 'sequence_number'],
)]
#[ORM\Index(name: 'IDX_FLIGHT_OCCURRENCE_OPERATIONAL_DATE', columns: ['operational_date'])]
#[ORM\Index(name: 'IDX_FLIGHT_OCCURRENCE_STATUS', columns: ['status'])]
final class FlightOccurrence extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $flightDefinitionId;

    #[ORM\Column(length: 16, enumType: FlightOccurrenceSource::class)]
    private FlightOccurrenceSource $source;

    #[ORM\Column(length: 16, enumType: FlightDirection::class)]
    private FlightDirection $direction;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $operationalDate;

    #[ORM\Column]
    private int $sequenceNumber;

    #[ORM\Column(length: 16)]
    private string $flightNumberSnapshot;

    #[ORM\Column(length: 3)]
    private string $originAirportCodeSnapshot;

    #[ORM\Column(length: 3)]
    private string $destinationAirportCodeSnapshot;

    #[ORM\Column(length: 32, enumType: FlightOccurrenceStatus::class)]
    private FlightOccurrenceStatus $status;

    /** @var list<array{id:string,code:string}> */
    #[ORM\Column(type: 'json')]
    private array $checkInCounters;

    /** @var array{id:string,code:string}|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $gate;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $lastAnnouncementId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $completedAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $cancelledAt;

    private function __construct()
    {
    }

    public static function createManual(FlightDefinition $flightDefinition, DateTimeImmutable $operationalDate, int $sequenceNumber = 1): self
    {
        return self::create($flightDefinition, FlightOccurrenceSource::Manual, $operationalDate, $sequenceNumber);
    }

    public static function create(
        FlightDefinition $flightDefinition,
        FlightOccurrenceSource $source,
        DateTimeImmutable $operationalDate,
        int $sequenceNumber = 1,
    ): self {
        if ($sequenceNumber < 1) {
            throw InvalidFlightOccurrenceTransitionException::forAction('create', 'invalid_sequence_number');
        }

        $now = self::now();
        $occurrence = new self();
        $occurrence->id = Uuid::v7();
        $occurrence->flightDefinitionId = $flightDefinition->getId();
        $occurrence->source = $source;
        $occurrence->direction = $flightDefinition->getDirection();
        $occurrence->operationalDate = self::dateOnly($operationalDate);
        $occurrence->sequenceNumber = $sequenceNumber;
        $occurrence->flightNumberSnapshot = $flightDefinition->getFlightNumber()->toString();
        $occurrence->originAirportCodeSnapshot = $flightDefinition->getOriginAirportCode()->toString();
        $occurrence->destinationAirportCodeSnapshot = $flightDefinition->getDestinationAirportCode()->toString();
        $occurrence->status = FlightOccurrenceStatus::Scheduled;
        $occurrence->checkInCounters = [];
        $occurrence->gate = null;
        $occurrence->lastAnnouncementId = null;
        $occurrence->createdAt = $now;
        $occurrence->updatedAt = $now;
        $occurrence->completedAt = null;
        $occurrence->cancelledAt = null;
        $occurrence->recordEvent(new FlightOccurrenceCreated(
            $occurrence->id->toRfc4122(),
            $occurrence->flightDefinitionId->toRfc4122(),
            $occurrence->operationalDate->format('Y-m-d'),
            $sequenceNumber,
            $now,
        ));

        return $occurrence;
    }

    /** @param list<array{id:string,code:string}> $checkInCounters */
    public function openCheckIn(string $announcementId, array $checkInCounters): void
    {
        $this->assertDeparture('check_in_opening');
        $this->assertStatus(FlightOccurrenceStatus::Scheduled, 'check_in_opening');
        $this->checkInCounters = $checkInCounters;
        $this->recordAnnouncement($announcementId);
        $this->status = FlightOccurrenceStatus::CheckInOpen;
        $this->recordEvent(new CheckInOpened($this->id->toRfc4122(), $announcementId, $this->updatedAt));
    }

    public function continueCheckIn(string $announcementId): void
    {
        $this->assertDeparture('check_in_continuation');
        $this->assertStatus(FlightOccurrenceStatus::CheckInOpen, 'check_in_continuation');
        $this->recordAnnouncement($announcementId);
        $this->recordEvent(new CheckInContinued($this->id->toRfc4122(), $announcementId, $this->updatedAt));
    }

    public function closeCheckIn(string $announcementId): void
    {
        $this->assertDeparture('check_in_closing');
        $this->assertStatus(FlightOccurrenceStatus::CheckInOpen, 'check_in_closing');
        $this->recordAnnouncement($announcementId);
        $this->status = FlightOccurrenceStatus::CheckInClosed;
        $this->recordEvent(new CheckInClosed($this->id->toRfc4122(), $announcementId, $this->updatedAt));
    }

    /** @param array{id:string,code:string} $gate */
    public function startBoarding(string $announcementId, array $gate): void
    {
        $this->assertDeparture('boarding_invitation');
        $this->assertStatus(FlightOccurrenceStatus::CheckInClosed, 'boarding_invitation');
        $this->gate = $gate;
        $this->recordAnnouncement($announcementId);
        $this->status = FlightOccurrenceStatus::Boarding;
        $this->recordEvent(new BoardingStarted($this->id->toRfc4122(), $announcementId, $this->updatedAt));
    }

    public function announceArrival(string $announcementId): void
    {
        $this->assertArrival('arrival');
        $this->assertStatus(FlightOccurrenceStatus::Scheduled, 'arrival');
        $this->recordAnnouncement($announcementId);
        $this->status = FlightOccurrenceStatus::ArrivalAnnounced;
        $this->recordEvent(new ArrivalAnnounced($this->id->toRfc4122(), $announcementId, $this->updatedAt));
    }

    public function complete(): void
    {
        if (in_array($this->status, [FlightOccurrenceStatus::Completed, FlightOccurrenceStatus::Cancelled], true)) {
            throw InvalidFlightOccurrenceTransitionException::forAction('complete', $this->status->value);
        }
        $this->status = FlightOccurrenceStatus::Completed;
        $this->completedAt = self::now();
        $this->updatedAt = $this->completedAt;
        $this->recordEvent(new FlightOccurrenceCompleted($this->id->toRfc4122(), $this->updatedAt));
    }

    public function cancel(): void
    {
        if (in_array($this->status, [FlightOccurrenceStatus::Completed, FlightOccurrenceStatus::Cancelled], true)) {
            throw InvalidFlightOccurrenceTransitionException::forAction('cancel', $this->status->value);
        }
        $this->status = FlightOccurrenceStatus::Cancelled;
        $this->cancelledAt = self::now();
        $this->updatedAt = $this->cancelledAt;
        $this->recordEvent(new FlightOccurrenceCancelled($this->id->toRfc4122(), $this->updatedAt));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFlightDefinitionId(): Uuid
    {
        return $this->flightDefinitionId;
    }

    public function getSource(): FlightOccurrenceSource
    {
        return $this->source;
    }

    public function getDirection(): FlightDirection
    {
        return $this->direction;
    }

    public function getOperationalDate(): DateTimeImmutable
    {
        return $this->operationalDate;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function getFlightNumberSnapshot(): string
    {
        return $this->flightNumberSnapshot;
    }

    public function getOriginAirportCodeSnapshot(): string
    {
        return $this->originAirportCodeSnapshot;
    }

    public function getDestinationAirportCodeSnapshot(): string
    {
        return $this->destinationAirportCodeSnapshot;
    }

    public function getStatus(): FlightOccurrenceStatus
    {
        return $this->status;
    }

    /** @return list<array{id:string,code:string}> */
    public function getCheckInCounters(): array
    {
        return $this->checkInCounters;
    }

    /** @return array{id:string,code:string}|null */
    public function getGate(): ?array
    {
        return $this->gate;
    }

    public function getLastAnnouncementId(): ?Uuid
    {
        return $this->lastAnnouncementId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    private function assertDeparture(string $action): void
    {
        if ($this->direction !== FlightDirection::Departure) {
            throw InvalidFlightOccurrenceTransitionException::incompatibleDirection($action, $this->direction->value);
        }
    }

    private function assertArrival(string $action): void
    {
        if ($this->direction !== FlightDirection::Arrival) {
            throw InvalidFlightOccurrenceTransitionException::incompatibleDirection($action, $this->direction->value);
        }
    }

    private function assertStatus(FlightOccurrenceStatus $expected, string $action): void
    {
        if ($this->status !== $expected) {
            throw InvalidFlightOccurrenceTransitionException::forAction($action, $this->status->value);
        }
    }

    private function recordAnnouncement(string $announcementId): void
    {
        $this->lastAnnouncementId = Uuid::fromString($announcementId);
        $this->updatedAt = self::now();
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private static function dateOnly(DateTimeImmutable $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date->format('Y-m-d'), new DateTimeZone('UTC'));
    }
}
