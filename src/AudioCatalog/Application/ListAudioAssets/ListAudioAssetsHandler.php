<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\ListAudioAssets;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListAudioAssetsHandler
{
    public function __construct(private AudioAssetRepositoryInterface $repository)
    {
    }

    /**
     * @return list<AudioAssetResult>
     */
    public function __invoke(ListAudioAssetsQuery $query): array
    {
        return array_map(
            static fn (AudioAsset $asset): AudioAssetResult => AudioAssetResult::fromEntity($asset),
            $this->repository->findActive(),
        );
    }
}
