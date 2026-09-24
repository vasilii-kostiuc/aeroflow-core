<?php

declare(strict_types=1);

namespace App\Tests\Application\AudioCatalog;

use App\AudioCatalog\Application\MoveAudioAssets\MoveAudioAssetsCommand;
use App\AudioCatalog\Application\MoveAudioAssets\MoveAudioAssetsHandler;
use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Event\AudioAssetUpdated;
use App\AudioCatalog\Domain\Exception\AudioAssetNotFoundException;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use App\Tests\Support\InMemoryAudioFolderRepository;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MoveAudioAssetsHandlerTest extends TestCase
{
    public function testMovesEveryAssetAndSkipsThoseAlreadyThere(): void
    {
        $folder = AudioFolder::create('Гонги', null);
        $a = $this->asset('a.wav');
        $b = $this->asset('b.wav', $folder->getId());

        $assets = $this->repository([$a, $b]);
        $assets->expects(self::once())->method('save')->with($a);
        $events = new RecordingEventPublisher();

        $results = $this->handler($assets, $folder, $events)(new MoveAudioAssetsCommand(
            [$a->getId()->toRfc4122(), $b->getId()->toRfc4122(), $a->getId()->toRfc4122()],
            $folder->getId()->toRfc4122(),
        ));

        self::assertCount(2, $results);
        self::assertSame([$folder->getId()->toRfc4122(), $folder->getId()->toRfc4122()], array_column($results, 'folderId'));
        self::assertCount(1, $events->messages);
        self::assertInstanceOf(AudioAssetUpdated::class, $events->messages[0]);
    }

    public function testUnknownAssetMovesNothing(): void
    {
        $a = $this->asset('a.wav');
        $assets = $this->repository([$a]);
        $assets->expects(self::never())->method('save');

        $this->expectException(AudioAssetNotFoundException::class);
        try {
            $this->handler($assets, null, new RecordingEventPublisher())(new MoveAudioAssetsCommand(
                [$a->getId()->toRfc4122(), Uuid::v7()->toRfc4122()],
                null,
            ));
        } finally {
            self::assertNull($a->getFolderId());
        }
    }

    public function testRejectsEmptySelection(): void
    {
        $this->expectException(InvalidAudioAssetException::class);
        $this->handler($this->createStub(AudioAssetRepositoryInterface::class), null, new RecordingEventPublisher())(
            new MoveAudioAssetsCommand([], null),
        );
    }

    /**
     * @param list<AudioAsset> $known
     */
    private function repository(array $known): AudioAssetRepositoryInterface&MockObject
    {
        $assets = $this->createMock(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturnCallback(static function (Uuid $id) use ($known): ?AudioAsset {
            foreach ($known as $asset) {
                if ($asset->getId()->equals($id)) {
                    return $asset;
                }
            }

            return null;
        });

        return $assets;
    }

    private function handler(
        AudioAssetRepositoryInterface $assets,
        ?AudioFolder $folder,
        RecordingEventPublisher $events,
    ): MoveAudioAssetsHandler {
        $folders = null === $folder ? new InMemoryAudioFolderRepository() : new InMemoryAudioFolderRepository($folder);

        return new MoveAudioAssetsHandler($assets, new AudioFolderResolver($folders), $events);
    }

    private function asset(string $name, ?Uuid $folderId = null): AudioAsset
    {
        $asset = AudioAsset::upload($name, LanguageCode::fromString('ru'), 'key', 'audio/wav', 10, null, $folderId);
        $asset->pullEvents();

        return $asset;
    }
}
