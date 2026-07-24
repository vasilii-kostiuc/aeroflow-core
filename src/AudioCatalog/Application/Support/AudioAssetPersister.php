<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Support;

use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Throwable;

/**
 * Stores an audio file and persists the AudioAsset built from its storage key as
 * one unit. Centralises the invariant shared by every asset-creation use case
 * (upload, generation): if persistence fails, the just-stored file must not be
 * left orphaned in storage.
 *
 * The caller only describes how to build the asset from the storage key (a pure
 * factory); storing, saving and the rollback belong here.
 */
final readonly class AudioAssetPersister
{
    public function __construct(
        private AudioAssetStorageInterface $storage,
        private AudioAssetRepositoryInterface $repository,
    ) {
    }

    /**
     * @param callable(string $storageKey): AudioAsset $build
     */
    public function persistFromFile(string $sourcePath, string $extension, callable $build): AudioAsset
    {
        return $this->persist($this->storage->store($sourcePath, $extension), $build);
    }

    /**
     * @param callable(string $storageKey): AudioAsset $build
     */
    public function persistFromContents(string $contents, string $extension, callable $build): AudioAsset
    {
        return $this->persist($this->storage->storeContents($contents, $extension), $build);
    }

    /**
     * @param callable(string $storageKey): AudioAsset $build
     */
    private function persist(string $storageKey, callable $build): AudioAsset
    {
        try {
            $asset = $build($storageKey);
            $this->repository->save($asset);

            return $asset;
        } catch (Throwable $exception) {
            $this->storage->delete($storageKey);

            throw $exception;
        }
    }
}
