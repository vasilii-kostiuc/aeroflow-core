<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\UpdateFlightDefinition;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\Service\FlightDefinitionUniquenessChecker;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateFlightDefinitionHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $repository,
        private FlightDefinitionUniquenessChecker $uniquenessChecker,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UpdateFlightDefinitionCommand $command): FlightDefinitionResult
    {
        $id = UuidParser::parse($command->id);
        $flightDefinition = $this->repository->findById($id)
            ?? throw FlightDefinitionNotFoundException::withId($command->id);
        $flightNumber = FlightNumber::fromString($command->flightNumber);
        $direction = FlightDirection::fromString($command->direction);
        $origin = AirportCode::fromString($command->originAirportCode);
        $destination = AirportCode::fromString($command->destinationAirportCode);

        $this->uniquenessChecker->ensureUnique($flightNumber, $direction, $origin, $destination, $id);

        if ($flightDefinition->updateDetails($flightNumber, $direction, $origin, $destination)) {
            $this->repository->save($flightDefinition);

            $this->events->publish(...$flightDefinition->pullEvents());
        }

        return FlightDefinitionResult::fromEntity($flightDefinition);
    }
}
