<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\GetFlightDefinition;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetFlightDefinitionHandler
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetFlightDefinitionQuery $query): FlightDefinitionResult
    {
        $id = UuidParser::parse($query->id);
        $flightDefinition = $this->repository->findById($id)
            ?? throw FlightDefinitionNotFoundException::withId($query->id);

        return FlightDefinitionResult::fromEntity($flightDefinition);
    }
}
