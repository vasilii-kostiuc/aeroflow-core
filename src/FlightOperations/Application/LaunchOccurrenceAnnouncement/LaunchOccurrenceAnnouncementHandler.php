<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\LaunchOccurrenceAnnouncement;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Application\Port\Announcements\OccurrenceAnnouncementPort;
use App\FlightOperations\Domain\Exception\FlightOccurrenceNotFoundException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Orchestrates a dispatcher action over a FlightOccurrence.
 *
 * The action both advances the occurrence lifecycle and creates the linked
 * Announcement, atomically. The aggregate is the sole authority on transition
 * validity. Announcement readiness is a precondition: if the launcher cannot
 * prepare the announcement, it throws and nothing is persisted. Both writes
 * commit in one local transaction (command.bus doctrine_transaction middleware).
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class LaunchOccurrenceAnnouncementHandler
{
    public function __construct(
        private FlightOccurrenceRepositoryInterface $occurrences,
        private OccurrenceAnnouncementPort $announcements,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(LaunchOccurrenceAnnouncementCommand $command): LaunchOccurrenceAnnouncementResult
    {
        $occurrence = $this->occurrences->findById(UuidParser::parse($command->flightOccurrenceId))
            ?? throw FlightOccurrenceNotFoundException::withId($command->flightOccurrenceId);

        $launched = $this->announcements->launch(
            $occurrence->getFlightDefinitionId()->toRfc4122(),
            $occurrence->getId()->toRfc4122(),
            $command->announcementType,
            $command->languages,
            $command->checkInCounterIds,
            $command->gateId,
        );

        match ($command->announcementType) {
            'check_in_opening' => $occurrence->openCheckIn($launched->announcementId, $launched->checkInCounters),
            'check_in_closing' => $occurrence->closeCheckIn($launched->announcementId),
            'boarding_invitation' => $occurrence->startBoarding($launched->announcementId, $launched->gate ?? []),
            'arrival' => $occurrence->announceArrival($launched->announcementId),
            default => throw InvalidFlightOccurrenceTransitionException::forAction($command->announcementType, $occurrence->getStatus()->value),
        };

        // The same dispatcher action drives the continuation repeat series (task 020),
        // atomically with the transition: opening starts it (if the flight configures a
        // continuation), closing ends it. Playback owns the repeat cadence; Core only
        // marks the start and the boundary.
        match ($command->announcementType) {
            'check_in_opening' => $this->announcements->launchContinuationIfConfigured(
                $occurrence->getFlightDefinitionId()->toRfc4122(),
                $occurrence->getId()->toRfc4122(),
                $command->checkInCounterIds,
            ),
            'check_in_closing' => $this->announcements->endContinuationRepeat($occurrence->getId()->toRfc4122()),
            default => null,
        };

        $this->occurrences->save($occurrence);
        $this->events->publish(...$occurrence->pullEvents());

        return new LaunchOccurrenceAnnouncementResult(
            FlightOccurrenceResult::fromEntity($occurrence),
            $launched->announcementId,
        );
    }
}
