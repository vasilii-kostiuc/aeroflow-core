<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\CreateFlightDefinition;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\Service\FlightDefinitionUniquenessChecker;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateFlightDefinitionHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $repository,
        private FlightDefinitionUniquenessChecker $uniquenessChecker,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateFlightDefinitionCommand $command): FlightDefinitionResult
    {
        $flightNumber = FlightNumber::fromString($command->flightNumber);
        $direction = FlightDirection::fromString($command->direction);
        $origin = AirportCode::fromString($command->originAirportCode);
        $destination = AirportCode::fromString($command->destinationAirportCode);

        $this->uniquenessChecker->ensureUnique($flightNumber, $direction, $origin, $destination);

        $flightDefinition = FlightDefinition::create($flightNumber, $direction, $origin, $destination);
        $this->repository->save($flightDefinition);

        $this->events->publish(...$flightDefinition->pullEvents());

        return FlightDefinitionResult::fromEntity($flightDefinition);
    }
}
