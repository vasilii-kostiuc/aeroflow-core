<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Storage;

interface AudioAssetStorageInterface
{
    public function store(string $sourcePath, string $extension): string;

    /**
     * Stores raw bytes as a new asset file, returning its opaque storage key.
     * Keeps in-memory content (e.g. TTS output) off a temporary file in the
     * caller: spooling, if needed at all, belongs to the backing store.
     */
    public function storeContents(string $contents, string $extension): string;

    public function delete(string $storageKey): void;

    /**
     * Opens the stored file for reading. The storage key stays opaque: callers never
     * learn paths or layout, so swapping the backing store (local disk, S3, ...)
     * only adds an implementation of this interface.
     *
     * @return resource|null a readable stream, or null when the file is missing
     */
    public function readStream(string $storageKey);
}
