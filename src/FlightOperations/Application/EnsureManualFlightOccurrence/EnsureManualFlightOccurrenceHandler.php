<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EnsureManualFlightOccurrence;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Application\OperationalDay\OperationalDayProviderInterface;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightOccurrenceSource;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Exception\FlightOccurrenceTransitionConflictException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Idempotent manual occurrence creation for the dispatcher board.
 *
 * Returns the existing occurrence for the business key (manual source) or
 * creates a new scheduled occurrence. Unlike CreateFlightOccurrence it does not
 * fail on a duplicate business key, so the dispatcher can ensure an occurrence
 * exists before launching the first announcement of the day.
 *
 * The operational date is today's operational day of the airport (task 027). A new
 * run is not created while the card still has a run started on the previous day
 * and not yet finished — a card never has two live runs.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class EnsureManualFlightOccurrenceHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $flightDefinitions,
        private FlightOccurrenceRepositoryInterface $occurrences,
        private OperationalDayProviderInterface $operationalDay,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(EnsureManualFlightOccurrenceCommand $command): FlightOccurrenceResult
    {
        $flightDefinitionId = UuidParser::parse($command->flightDefinitionId);
        $flightDefinition = $this->flightDefinitions->findById($flightDefinitionId)
            ?? throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);

        if (!$flightDefinition->isActive()) {
            throw InvalidFlightOccurrenceTransitionException::forAction('create', 'inactive_flight_definition');
        }

        $operationalDate = $this->operationalDay->today();

        $existing = $this->occurrences->findOneByBusinessKey(
            $flightDefinitionId,
            $operationalDate,
            FlightOccurrenceSource::Manual,
            $command->sequenceNumber,
        );
        if ($existing !== null) {
            return FlightOccurrenceResult::fromEntity($existing);
        }

        $carriedOver = $this->occurrences->findLatestManualForUpdate($flightDefinitionId, $operationalDate->modify('-1 day'));
        if ($carriedOver !== null && $carriedOver->isInProgress()) {
            throw FlightOccurrenceTransitionConflictException::previousManualRunNotFinished($carriedOver->getStatus()->value);
        }

        $occurrence = FlightOccurrence::create($flightDefinition, FlightOccurrenceSource::Manual, $operationalDate, $command->sequenceNumber);
        $this->occurrences->save($occurrence);
        $this->events->publish(...$occurrence->pullEvents());

        return FlightOccurrenceResult::fromEntity($occurrence);
    }
}
