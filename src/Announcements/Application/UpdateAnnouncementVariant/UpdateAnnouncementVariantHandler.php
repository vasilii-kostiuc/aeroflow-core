<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateAnnouncementVariant;

use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\AnnouncementVariantSourceType;
use App\Announcements\Domain\Exception\FlightAnnouncementConfigNotFoundException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Announcements\Domain\ValueObject\LanguageCode;
use App\AudioCatalog\Domain\Exception\AudioAssetUnavailableException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateAnnouncementVariantHandler
{
    public function __construct(
        private FlightAnnouncementConfigRepositoryInterface $repository,
        private AudioAssetRepositoryInterface $audioAssets,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateAnnouncementVariantCommand $command): FlightAnnouncementConfigResult
    {
        $config = $this->findConfig($command->flightDefinitionId, $command->configId);
        $this->assertAudioAssetIsAvailable($command->sourceType, $command->audioAssetId);
        $config->updateVariant(
            $command->variantId,
            LanguageCode::fromString($command->languageCode),
            $command->sortOrder,
            AnnouncementVariantSourceType::from($command->sourceType),
            $command->audioAssetId,
            $command->text,
            $command->enabled,
        );

        $this->repository->save($config);
        $this->dispatchEvents($config);

        return FlightAnnouncementConfigResult::fromEntity($config);
    }

    private function assertAudioAssetIsAvailable(string $sourceType, ?string $audioAssetId): void
    {
        if (AnnouncementVariantSourceType::AudioAsset->value !== $sourceType) {
            return;
        }

        if ($audioAssetId === null || !Uuid::isValid($audioAssetId)) {
            throw AudioAssetUnavailableException::withId((string) $audioAssetId);
        }

        $asset = $this->audioAssets->findById(Uuid::fromString($audioAssetId));
        if ($asset === null || !$asset->isActive()) {
            throw AudioAssetUnavailableException::withId($audioAssetId);
        }
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
