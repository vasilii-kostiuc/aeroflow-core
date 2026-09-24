<?php

declare(strict_types=1);

namespace App\Tests\Application\AudioCatalog;

use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Application\UpdateAudioAsset\UpdateAudioAssetCommand;
use App\AudioCatalog\Application\UpdateAudioAsset\UpdateAudioAssetHandler;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Event\AudioAssetUpdated;
use App\AudioCatalog\Domain\Exception\AudioAssetNotFoundException;
use App\AudioCatalog\Domain\Exception\AudioFolderNotFoundException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use App\Tests\Support\InMemoryAudioFolderRepository;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAudioAssetHandlerTest extends TestCase
{
    public function testRenamesAndMovesAsset(): void
    {
        $folder = AudioFolder::create('Вылеты', null);
        $asset = $this->asset();

        $assets = $this->createMock(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn($asset);
        $assets->expects(self::once())->method('save')->with($asset);
        $events = new RecordingEventPublisher();

        $result = $this->handler($assets, new InMemoryAudioFolderRepository($folder), $events)(
            new UpdateAudioAssetCommand($asset->getId()->toRfc4122(), 'Выход A12', $folder->getId()->toRfc4122()),
        );

        self::assertSame('Выход A12', $result->title);
        self::assertSame($folder->getId()->toRfc4122(), $result->folderId);
        self::assertSame('gate-a12-en.wav', $result->name);
        self::assertCount(1, $events->messages);
        self::assertInstanceOf(AudioAssetUpdated::class, $events->messages[0]);
    }

    public function testUnchangedDetailsAreNotSavedNorPublished(): void
    {
        $asset = $this->asset();

        $assets = $this->createMock(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn($asset);
        $assets->expects(self::never())->method('save');
        $events = new RecordingEventPublisher();

        $this->handler($assets, new InMemoryAudioFolderRepository(), $events)(
            new UpdateAudioAssetCommand($asset->getId()->toRfc4122(), $asset->getTitle(), null),
        );

        self::assertCount(0, $events->messages);
    }

    public function testUnknownAssetGives404(): void
    {
        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn(null);

        $this->expectException(AudioAssetNotFoundException::class);
        $this->handler($assets, new InMemoryAudioFolderRepository(), new RecordingEventPublisher())(
            new UpdateAudioAssetCommand(Uuid::v7()->toRfc4122(), 'x', null),
        );
    }

    public function testUnknownFolderGives404(): void
    {
        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn($this->asset());

        $this->expectException(AudioFolderNotFoundException::class);
        $this->handler($assets, new InMemoryAudioFolderRepository(), new RecordingEventPublisher())(
            new UpdateAudioAssetCommand(Uuid::v7()->toRfc4122(), 'x', Uuid::v7()->toRfc4122()),
        );
    }

    private function handler(
        AudioAssetRepositoryInterface $assets,
        InMemoryAudioFolderRepository $folders,
        RecordingEventPublisher $events,
    ): UpdateAudioAssetHandler {
        return new UpdateAudioAssetHandler($assets, new AudioFolderResolver($folders), $events);
    }

    private function asset(): AudioAsset
    {
        $asset = AudioAsset::upload('gate-a12-en.wav', LanguageCode::fromString('en'), 'key', 'audio/wav', 10);
        $asset->pullEvents();

        return $asset;
    }
}
