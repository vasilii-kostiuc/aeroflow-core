<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\FlightOperations;

use App\Announcements\Application\Port\FlightOperations\FlightOccurrenceLookupInterface;
use App\Announcements\Application\Port\FlightOperations\FlightOccurrenceSnapshot;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Enum\FlightOccurrenceStatus;
use App\FlightOperations\Domain\Exception\FlightOccurrenceNotFoundException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Uid\Uuid;

final readonly class FlightOperationsFlightOccurrenceLookup implements FlightOccurrenceLookupInterface
{
    public function __construct(
        private FlightOccurrenceRepositoryInterface $occurrences,
        private DomainEventPublisher $events,
    ) {
    }

    public function findById(string $id): ?FlightOccurrenceSnapshot
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        $occurrence = $this->occurrences->findById(Uuid::fromString($id));
        if ($occurrence === null) {
            return null;
        }

        return new FlightOccurrenceSnapshot(
            id: $occurrence->getId()->toRfc4122(),
            flightDefinitionId: $occurrence->getFlightDefinitionId()->toRfc4122(),
            direction: $occurrence->getDirection()->value,
            status: $occurrence->getStatus()->value,
        );
    }

    public function assertCanLaunch(string $id, string $announcementType): void
    {
        $occurrence = $this->findOccurrence($id);
        $status = $occurrence->getStatus();
        $direction = $occurrence->getDirection();

        match ($announcementType) {
            'check_in_opening' => $this->assertState($announcementType, $direction, FlightDirection::Departure, $status, FlightOccurrenceStatus::Scheduled),
            'check_in_closing' => $this->assertState($announcementType, $direction, FlightDirection::Departure, $status, FlightOccurrenceStatus::CheckInOpen),
            'boarding_invitation' => $this->assertState($announcementType, $direction, FlightDirection::Departure, $status, FlightOccurrenceStatus::CheckInClosed),
            'arrival' => $this->assertState($announcementType, $direction, FlightDirection::Arrival, $status, FlightOccurrenceStatus::Scheduled),
            default => throw InvalidFlightOccurrenceTransitionException::forAction($announcementType, $status->value),
        };
    }

    public function recordAnnouncementLaunch(
        string $id,
        string $announcementType,
        string $announcementId,
        array $checkInCounters,
        ?array $gate,
    ): void {
        $occurrence = $this->findOccurrence($id);

        match ($announcementType) {
            'check_in_opening' => $occurrence->openCheckIn($announcementId, $checkInCounters),
            'check_in_closing' => $occurrence->closeCheckIn($announcementId),
            'boarding_invitation' => $occurrence->startBoarding($announcementId, $gate ?? []),
            'arrival' => $occurrence->announceArrival($announcementId),
            default => throw InvalidFlightOccurrenceTransitionException::forAction($announcementType, $occurrence->getStatus()->value),
        };

        $this->occurrences->save($occurrence);
        $this->events->publish(...$occurrence->pullEvents());
    }

    private function findOccurrence(string $id): FlightOccurrence
    {
        if (!Uuid::isValid($id)) {
            throw FlightOccurrenceNotFoundException::withId($id);
        }

        return $this->occurrences->findById(Uuid::fromString($id))
            ?? throw FlightOccurrenceNotFoundException::withId($id);
    }

    private function assertState(
        string $action,
        FlightDirection $actualDirection,
        FlightDirection $expectedDirection,
        FlightOccurrenceStatus $actualStatus,
        FlightOccurrenceStatus $expectedStatus,
    ): void {
        if ($actualDirection !== $expectedDirection) {
            throw InvalidFlightOccurrenceTransitionException::incompatibleDirection($action, $actualDirection->value);
        }

        if ($actualStatus !== $expectedStatus) {
            throw InvalidFlightOccurrenceTransitionException::forAction($action, $actualStatus->value);
        }
    }
}
