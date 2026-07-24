<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Support;

use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use Throwable;

/**
 * Stores an audio file and hands its opaque storage key to the caller, cleaning
 * the just-stored file up if the caller throws. Centralises the invariant shared
 * by every asset-creation use case (upload, generation): a failed persist must
 * never leave an orphaned file behind in storage.
 *
 * The caller keeps ownership of building and saving the AudioAsset; this only
 * guards the storage side, so the repository stays in the handler where other
 * work (cache lookup, deactivating superseded assets) needs it too.
 */
final readonly class StoredAudioFile
{
    public function __construct(private AudioAssetStorageInterface $storage)
    {
    }

    /**
     * @param callable(string $storageKey): AudioAsset $consume
     */
    public function fromFile(string $sourcePath, string $extension, callable $consume): AudioAsset
    {
        return $this->guard($this->storage->store($sourcePath, $extension), $consume);
    }

    /**
     * @param callable(string $storageKey): AudioAsset $consume
     */
    public function fromContents(string $contents, string $extension, callable $consume): AudioAsset
    {
        return $this->guard($this->storage->storeContents($contents, $extension), $consume);
    }

    /**
     * @param callable(string $storageKey): AudioAsset $consume
     */
    private function guard(string $storageKey, callable $consume): AudioAsset
    {
        try {
            return $consume($storageKey);
        } catch (Throwable $exception) {
            $this->storage->delete($storageKey);

            throw $exception;
        }
    }
}
