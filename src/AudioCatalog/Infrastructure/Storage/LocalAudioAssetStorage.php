<?php

declare(strict_types=1);

namespace App\AudioCatalog\Infrastructure\Storage;

use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Uuid;

final readonly class LocalAudioAssetStorage implements AudioAssetStorageInterface
{
    public function __construct(
        private string $storageDirectory,
        private Filesystem $filesystem,
    ) {
    }

    public function store(string $sourcePath, string $extension): string
    {
        $this->filesystem->mkdir($this->storageDirectory, 0o750);
        $storageKey = sprintf('%s.%s', Uuid::v7()->toRfc4122(), $extension);
        $targetPath = $this->storageDirectory.DIRECTORY_SEPARATOR.$storageKey;

        if (!copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Unable to store uploaded audio file.');
        }

        return $storageKey;
    }

    public function delete(string $storageKey): void
    {
        $this->filesystem->remove($this->storageDirectory.DIRECTORY_SEPARATOR.basename($storageKey));
    }

    public function readStream(string $storageKey)
    {
        $path = $this->storageDirectory.DIRECTORY_SEPARATOR.basename($storageKey);
        if (!is_file($path)) {
            return null;
        }

        $stream = fopen($path, 'rb');

        return $stream === false ? null : $stream;
    }
}
