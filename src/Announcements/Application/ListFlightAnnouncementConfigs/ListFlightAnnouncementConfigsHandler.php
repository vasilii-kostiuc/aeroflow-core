<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListFlightAnnouncementConfigs;

use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListFlightAnnouncementConfigsHandler
{
    public function __construct(
        private FlightAnnouncementConfigRepositoryInterface $repository,
        private FlightDefinitionRepositoryInterface $flightDefinitions,
    ) {
    }

    /**
     * @return list<FlightAnnouncementConfigResult>
     */
    public function __invoke(ListFlightAnnouncementConfigsQuery $query): array
    {
        if (!Uuid::isValid($query->flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($query->flightDefinitionId);
        }

        $flightDefinitionId = Uuid::fromString($query->flightDefinitionId);
        $flightDefinition = $this->flightDefinitions->findById($flightDefinitionId)
            ?? throw FlightDefinitionNotFoundException::withId($query->flightDefinitionId);

        return array_map(
            static fn ($config): FlightAnnouncementConfigResult => FlightAnnouncementConfigResult::fromEntity(
                $config,
                $config->getAnnouncementType()->isCompatibleWith($flightDefinition->getDirection()),
            ),
            $this->repository->findByFlightDefinitionId($flightDefinitionId),
        );
    }
}
