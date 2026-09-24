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

    /**
     * Active generated assets that carry the same logical TTS content
     * (text + language + voice), regardless of the voice model version. Used to
     * hit the generation cache and to retire assets produced by an older model.
     *
     * @return list<AudioAsset>
     */
    public function findActiveGeneratedByContent(string $textHash, string $languageCode, string $voice): array;

    public function countActiveInFolder(Uuid $folderId): int;

    /**
     * Inactive assets still pointing at the folder. They are invisible in the
     * library, so deleting their folder moves them to the root instead of
     * blocking the delete.
     *
     * @return list<AudioAsset>
     */
    public function findInactiveInFolder(Uuid $folderId): array;
}
