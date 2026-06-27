<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\StartNextManualFlightOccurrence;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Exception\FlightOccurrenceTransitionConflictException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Starts a new manual run of the same flight card on the same operational date.
 *
 * Unlike EnsureManualFlightOccurrence (which serves the first run, idempotent on
 * sequenceNumber=1), this allocates the next sequence number on the server. It is
 * deliberately not idempotent on a fixed key: protection against duplicates and a
 * double click comes from domain state — a successor run may only start once the
 * latest run has reached the final status of its lifecycle. The latest run is read
 * for update so concurrent commands cannot allocate the same sequence number, and
 * the business-key unique index is the last line of defence.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class StartNextManualFlightOccurrenceHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $flightDefinitions,
        private FlightOccurrenceRepositoryInterface $occurrences,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(StartNextManualFlightOccurrenceCommand $command): FlightOccurrenceResult
    {
        $flightDefinitionId = UuidParser::parse($command->flightDefinitionId);
        $flightDefinition = $this->flightDefinitions->findById($flightDefinitionId)
            ?? throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);

        if (!$flightDefinition->isActive()) {
            throw InvalidFlightOccurrenceTransitionException::forAction('create', 'inactive_flight_definition');
        }

        $operationalDate = $this->parseOperationalDate($command->operationalDate);

        $latest = $this->occurrences->findLatestManualForUpdate($flightDefinitionId, $operationalDate);
        if ($latest === null) {
            throw FlightOccurrenceTransitionConflictException::noPreviousManualRun();
        }
        if (!$latest->hasReachedFinalStatus()) {
            throw FlightOccurrenceTransitionConflictException::previousManualRunNotFinished($latest->getStatus()->value);
        }

        $occurrence = FlightOccurrence::createManual(
            $flightDefinition,
            $operationalDate,
            $latest->getSequenceNumber() + 1,
        );
        $this->occurrences->save($occurrence);
        $this->events->publish(...$occurrence->pullEvents());

        return FlightOccurrenceResult::fromEntity($occurrence);
    }

    private function parseOperationalDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed instanceof DateTimeImmutable || $parsed->format('Y-m-d') !== $date) {
            throw InvalidFlightOccurrenceTransitionException::forAction('create', 'invalid_operational_date');
        }

        return $parsed;
    }
}
