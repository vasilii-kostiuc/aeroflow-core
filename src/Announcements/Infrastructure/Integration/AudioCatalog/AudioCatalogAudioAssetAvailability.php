<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\AudioCatalog;

use App\Announcements\Application\Port\AudioCatalog\AudioAssetAvailabilityInterface;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AudioCatalogAudioAssetAvailability implements AudioAssetAvailabilityInterface
{
    public function __construct(private AudioAssetRepositoryInterface $repository)
    {
    }

    public function isAvailable(Uuid $id): bool
    {
        $audioAsset = $this->repository->findById($id);

        return $audioAsset !== null && $audioAsset->isActive();
    }
}
