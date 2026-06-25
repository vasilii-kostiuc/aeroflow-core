<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateAnnouncementVariant;

use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Application\Service\AnnouncementSegmentsValidator;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Exception\FlightAnnouncementConfigNotFoundException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateAnnouncementVariantHandler
{
    public function __construct(
        private FlightAnnouncementConfigRepositoryInterface $repository,
        private AnnouncementSegmentsValidator $segmentsValidator,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UpdateAnnouncementVariantCommand $command): FlightAnnouncementConfigResult
    {
        $config = $this->findConfig($command->flightDefinitionId, $command->configId);
        $this->segmentsValidator->validate($command->segments);
        $config->updateVariant(
            $command->variantId,
            LanguageCode::fromString($command->languageCode),
            $command->sortOrder,
            $command->segments,
            $command->enabled,
        );

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
        $this->events->publish(...$config->pullEvents());
    }
}
