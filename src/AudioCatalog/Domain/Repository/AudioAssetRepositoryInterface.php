<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Repository;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use Symfony\Component\Uid\Uuid;

interface AudioAssetRepositoryInterface
{
    public function save(AudioAsset $audioAsset): void;

    public function findById(Uuid $id): ?AudioAsset;

    /**
     * @return list<AudioAsset>
     */
    public function findActive(): array;
}
