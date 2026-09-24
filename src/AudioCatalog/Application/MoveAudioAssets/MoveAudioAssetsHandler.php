<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\MoveAudioAssets;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Exception\AudioAssetNotFoundException;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Group move of the library screen (drag several files onto a folder). All or
 * nothing: every asset is loaded before any is changed, and command.bus wraps the
 * handler in one transaction, so an unknown id leaves every asset where it was.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class MoveAudioAssetsHandler
{
    public const int MAX_ASSETS = 500;

    public function __construct(
        private AudioAssetRepositoryInterface $assets,
        private AudioFolderResolver $folders,
        private DomainEventPublisher $events,
    ) {
    }

    /**
     * @return list<AudioAssetResult>
     */
    public function __invoke(MoveAudioAssetsCommand $command): array
    {
        $ids = array_values(array_unique($command->assetIds));
        if ([] === $ids) {
            throw InvalidAudioAssetException::nothingToMove();
        }
        if (count($ids) > self::MAX_ASSETS) {
            throw InvalidAudioAssetException::tooManyToMove(self::MAX_ASSETS);
        }

        $folderId = $this->folders->resolveId($command->folderId);
        $assets = array_map($this->load(...), $ids);

        $events = [];
        foreach ($assets as $asset) {
            if ($asset->updateDetails($asset->getTitle(), $folderId)) {
                $this->assets->save($asset);
                array_push($events, ...$asset->pullEvents());
            }
        }
        $this->events->publish(...$events);

        return array_map(static fn (AudioAsset $asset): AudioAssetResult => AudioAssetResult::fromEntity($asset), $assets);
    }

    private function load(string $id): AudioAsset
    {
        if (!Uuid::isValid($id)) {
            throw AudioAssetNotFoundException::withId($id);
        }

        return $this->assets->findById(Uuid::fromString($id))
            ?? throw AudioAssetNotFoundException::withId($id);
    }
}
