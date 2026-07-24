<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Infrastructure\Storage;

use App\AudioCatalog\Infrastructure\Storage\LocalAudioAssetStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class LocalAudioAssetStorageTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/audio-storage-test-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
    }

    public function testReadStreamReturnsStoredFileContent(): void
    {
        $storage = new LocalAudioAssetStorage($this->directory, new Filesystem());
        $source = tempnam(sys_get_temp_dir(), 'audio-src-');
        self::assertIsString($source);
        file_put_contents($source, 'RIFF-payload');

        $storageKey = $storage->store($source, 'wav');
        $stream = $storage->readStream($storageKey);

        self::assertIsResource($stream);
        self::assertSame('RIFF-payload', stream_get_contents($stream));
        fclose($stream);
        unlink($source);
    }

    public function testStoreContentsWritesBytesReadableBackThroughStream(): void
    {
        $storage = new LocalAudioAssetStorage($this->directory, new Filesystem());

        $storageKey = $storage->storeContents('RIFF-generated-bytes', 'wav');
        $stream = $storage->readStream($storageKey);

        self::assertStringEndsWith('.wav', $storageKey);
        self::assertIsResource($stream);
        self::assertSame('RIFF-generated-bytes', stream_get_contents($stream));
        fclose($stream);
    }

    public function testReadStreamReturnsNullForMissingFile(): void
    {
        $storage = new LocalAudioAssetStorage($this->directory, new Filesystem());

        self::assertNull($storage->readStream('unknown.wav'));
    }

    public function testReadStreamNeverEscapesTheStorageDirectory(): void
    {
        $storage = new LocalAudioAssetStorage($this->directory, new Filesystem());

        // A hostile storage key must not read files outside the storage directory.
        self::assertNull($storage->readStream('../../etc/passwd'));
    }
}
