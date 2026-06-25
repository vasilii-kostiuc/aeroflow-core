<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ActivateFlightDefinition;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ActivateFlightDefinitionHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $repository,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(ActivateFlightDefinitionCommand $command): FlightDefinitionResult
    {
        $id = UuidParser::parse($command->id);
        $flightDefinition = $this->repository->findById($id)
            ?? throw FlightDefinitionNotFoundException::withId($command->id);

        if ($flightDefinition->activate()) {
            $this->repository->save($flightDefinition);

            $this->events->publish(...$flightDefinition->pullEvents());
        }

        return FlightDefinitionResult::fromEntity($flightDefinition);
    }
}
