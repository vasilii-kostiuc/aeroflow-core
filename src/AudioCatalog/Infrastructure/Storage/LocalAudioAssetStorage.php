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
        return $this->write($extension, static fn (string $target): bool => copy($sourcePath, $target));
    }

    public function storeContents(string $contents, string $extension): string
    {
        return $this->write($extension, static fn (string $target): bool => false !== file_put_contents($target, $contents));
    }

    /**
     * @param callable(string): bool $writer receives the target path, returns success
     */
    private function write(string $extension, callable $writer): string
    {
        $this->filesystem->mkdir($this->storageDirectory, 0o750);
        $storageKey = sprintf('%s.%s', Uuid::v7()->toRfc4122(), $extension);
        $targetPath = $this->storageDirectory.DIRECTORY_SEPARATOR.$storageKey;

        if (!$writer($targetPath)) {
            throw new RuntimeException('Unable to store audio file.');
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
