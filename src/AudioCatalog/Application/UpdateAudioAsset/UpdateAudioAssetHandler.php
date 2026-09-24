<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UpdateAudioAsset;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Domain\Exception\AudioAssetNotFoundException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateAudioAssetHandler
{
    public function __construct(
        private AudioAssetRepositoryInterface $assets,
        private AudioFolderResolver $folders,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UpdateAudioAssetCommand $command): AudioAssetResult
    {
        if (!Uuid::isValid($command->id)) {
            throw AudioAssetNotFoundException::withId($command->id);
        }
        $asset = $this->assets->findById(Uuid::fromString($command->id))
            ?? throw AudioAssetNotFoundException::withId($command->id);

        if ($asset->updateDetails($command->title, $this->folders->resolveId($command->folderId))) {
            $this->assets->save($asset);
            $this->events->publish(...$asset->pullEvents());
        }

        return AudioAssetResult::fromEntity($asset);
    }
}
