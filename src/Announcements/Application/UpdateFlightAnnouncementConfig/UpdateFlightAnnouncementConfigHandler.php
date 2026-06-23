<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateFlightAnnouncementConfig;

use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Exception\FlightAnnouncementConfigNotFoundException;
use App\Announcements\Domain\Exception\IncompatibleFlightAnnouncementTypeException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateFlightAnnouncementConfigHandler
{
    public function __construct(
        private FlightAnnouncementConfigRepositoryInterface $repository,
        private FlightDefinitionRepositoryInterface $flightDefinitions,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateFlightAnnouncementConfigCommand $command): FlightAnnouncementConfigResult
    {
        $config = $this->findConfig($command->flightDefinitionId, $command->configId);
        $flightDefinition = $this->flightDefinitions->findById(Uuid::fromString($command->flightDefinitionId))
            ?? throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);
        if ($command->enabled && !$config->getAnnouncementType()->isCompatibleWith($flightDefinition->getDirection())) {
            throw IncompatibleFlightAnnouncementTypeException::forDirection($config->getAnnouncementType()->value, $flightDefinition->getDirection()->value);
        }
        $config->changeSettings($command->enabled, $command->repeatEveryMinutes);
        $this->repository->save($config);
        $this->dispatchEvents($config);

        return FlightAnnouncementConfigResult::fromEntity($config);
    }

    private function findConfig(string $flightDefinitionId, string $configId): FlightAnnouncementConfig
    {
        if (!Uuid::isValid($flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($flightDefinitionId);
        }
        if (!Uuid::isValid($configId)) {
            throw FlightAnnouncementConfigNotFoundException::withId($configId);
        }

        $config = $this->repository->findById(Uuid::fromString($configId))
            ?? throw FlightAnnouncementConfigNotFoundException::withId($configId);

        if (!$config->getFlightDefinitionId()->equals(Uuid::fromString($flightDefinitionId))) {
            throw FlightAnnouncementConfigNotFoundException::withId($configId);
        }

        return $config;
    }

    private function dispatchEvents(FlightAnnouncementConfig $config): void
    {
        foreach ($config->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
