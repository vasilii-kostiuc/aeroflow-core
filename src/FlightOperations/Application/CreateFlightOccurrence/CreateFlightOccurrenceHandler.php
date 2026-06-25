<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\CreateFlightOccurrence;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightOccurrenceSource;
use App\FlightOperations\Domain\Exception\DuplicateFlightOccurrenceException;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Exception\InvalidFlightOccurrenceTransitionException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateFlightOccurrenceHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $flightDefinitions,
        private FlightOccurrenceRepositoryInterface $occurrences,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateFlightOccurrenceCommand $command): FlightOccurrenceResult
    {
        $flightDefinitionId = UuidParser::parse($command->flightDefinitionId);
        $flightDefinition = $this->flightDefinitions->findById($flightDefinitionId)
            ?? throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);

        if (!$flightDefinition->isActive()) {
            throw InvalidFlightOccurrenceTransitionException::forAction('create', 'inactive_flight_definition');
        }

        $source = FlightOccurrenceSource::tryFrom($command->source)
            ?? throw InvalidFlightOccurrenceTransitionException::forAction('create', 'invalid_source');
        $operationalDate = $this->parseOperationalDate($command->operationalDate);

        if ($this->occurrences->findOneByBusinessKey($flightDefinitionId, $operationalDate, $source, $command->sequenceNumber) !== null) {
            throw DuplicateFlightOccurrenceException::forBusinessKey();
        }

        $occurrence = FlightOccurrence::create($flightDefinition, $source, $operationalDate, $command->sequenceNumber);
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
