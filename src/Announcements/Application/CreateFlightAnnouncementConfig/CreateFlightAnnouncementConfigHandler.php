<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateFlightAnnouncementConfig;

use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\DuplicateFlightAnnouncementConfigException;
use App\Announcements\Domain\Exception\FlightDefinitionNotFoundException;
use App\Announcements\Domain\Exception\IncompatibleFlightAnnouncementTypeException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateFlightAnnouncementConfigHandler
{
    public function __construct(
        private FlightAnnouncementConfigRepositoryInterface $repository,
        private FlightDefinitionLookupInterface $flightDefinitions,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateFlightAnnouncementConfigCommand $command): FlightAnnouncementConfigResult
    {
        if (!Uuid::isValid($command->flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($command->flightDefinitionId);
        }

        $flightDefinitionId = Uuid::fromString($command->flightDefinitionId);
        $flightDefinition = $this->flightDefinitions->findById($flightDefinitionId)
            ?? throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);
        $type = FlightAnnouncementType::from($command->announcementType);

        if (!$type->isCompatibleWith($flightDefinition->direction)) {
            throw IncompatibleFlightAnnouncementTypeException::forDirection($type->value, $flightDefinition->direction->value);
        }

        if ($this->repository->findOneForFlightAndType($flightDefinitionId, $type) !== null) {
            throw DuplicateFlightAnnouncementConfigException::forFlightAndType($command->flightDefinitionId, $type->value);
        }

        $config = FlightAnnouncementConfig::create(
            $command->flightDefinitionId,
            $type,
            $command->enabled,
            $command->repeatEveryMinutes,
        );

        $this->repository->save($config);
        $this->dispatchEvents($config);

        return FlightAnnouncementConfigResult::fromEntity($config);
    }

    private function dispatchEvents(FlightAnnouncementConfig $config): void
    {
        $this->events->publish(...$config->pullEvents());
    }
}
