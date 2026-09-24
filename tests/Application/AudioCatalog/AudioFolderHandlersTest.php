<?php

declare(strict_types=1);

namespace App\Tests\Application\AudioCatalog;

use App\AudioCatalog\Application\AudioFolderResult;
use App\AudioCatalog\Application\CreateAudioFolder\CreateAudioFolderCommand;
use App\AudioCatalog\Application\CreateAudioFolder\CreateAudioFolderHandler;
use App\AudioCatalog\Application\DeleteAudioFolder\DeleteAudioFolderCommand;
use App\AudioCatalog\Application\DeleteAudioFolder\DeleteAudioFolderHandler;
use App\AudioCatalog\Application\Support\AudioFolderHierarchy;
use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Application\UpdateAudioFolder\UpdateAudioFolderCommand;
use App\AudioCatalog\Application\UpdateAudioFolder\UpdateAudioFolderHandler;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Event\AudioAssetUpdated;
use App\AudioCatalog\Domain\Event\AudioFolderCreated;
use App\AudioCatalog\Domain\Event\AudioFolderDeleted;
use App\AudioCatalog\Domain\Event\AudioFolderMoved;
use App\AudioCatalog\Domain\Exception\AudioFolderNotEmptyException;
use App\AudioCatalog\Domain\Exception\AudioFolderNotFoundException;
use App\AudioCatalog\Domain\Exception\DuplicateAudioFolderException;
use App\AudioCatalog\Domain\Exception\InvalidAudioFolderException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use App\Tests\Support\InMemoryAudioFolderRepository;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AudioFolderHandlersTest extends TestCase
{
    private InMemoryAudioFolderRepository $folders;
    private RecordingEventPublisher $events;

    protected function setUp(): void
    {
        $this->folders = new InMemoryAudioFolderRepository();
        $this->events = new RecordingEventPublisher();
    }

    public function testCreatesTopLevelAndNestedFolders(): void
    {
        $departures = $this->create('Вылеты');
        $flight = $this->create('SU 1234', $departures->id);

        self::assertNull($departures->parentId);
        self::assertSame($departures->id, $flight->parentId);
        self::assertCount(2, $this->events->messages);
        self::assertInstanceOf(AudioFolderCreated::class, $this->events->messages[1]);
    }

    public function testSiblingNamesAreUniqueCaseInsensitively(): void
    {
        $departures = $this->create('Вылеты');
        $this->create('SU 1234', $departures->id);
        // The same name under another parent is fine.
        $this->create('SU 1234');

        $this->expectException(DuplicateAudioFolderException::class);
        $this->create('su 1234', $departures->id);
    }

    public function testDuplicateTopLevelNameIsRejected(): void
    {
        $this->create('Гонги');

        $this->expectException(DuplicateAudioFolderException::class);
        $this->create('ГОНГИ');
    }

    public function testUnknownParentGives404(): void
    {
        $this->expectException(AudioFolderNotFoundException::class);
        $this->create('SU 1234', Uuid::v7()->toRfc4122());
    }

    public function testDepthIsLimited(): void
    {
        $parentId = null;
        for ($level = 1; $level <= AudioFolder::MAX_DEPTH; ++$level) {
            $parentId = $this->create('L'.$level, $parentId)->id;
        }

        $this->expectException(InvalidAudioFolderException::class);
        $this->create('too deep', $parentId);
    }

    public function testMovingIntoDescendantIsRejected(): void
    {
        $departures = $this->create('Вылеты');
        $flight = $this->create('SU 1234', $departures->id);

        $this->expectException(InvalidAudioFolderException::class);
        $this->update($departures->id, 'Вылеты', $flight->id);
    }

    public function testMovingSubtreeRespectsDepth(): void
    {
        // a/b/c (height 3) under x/y (depth 2) would reach level 5.
        $a = $this->create('a');
        $b = $this->create('b', $a->id);
        $this->create('c', $b->id);
        $x = $this->create('x');
        $y = $this->create('y', $x->id);

        $this->expectException(InvalidAudioFolderException::class);
        $this->update($a->id, 'a', $y->id);
    }

    public function testRenamesAndMovesInOneCommand(): void
    {
        $departures = $this->create('Вылеты');
        $flight = $this->create('SU 1234');
        $this->events->messages = [];

        $result = $this->update($flight->id, 'SU 1234 (Москва)', $departures->id);

        self::assertSame('SU 1234 (Москва)', $result->name);
        self::assertSame($departures->id, $result->parentId);
        self::assertCount(2, $this->events->messages);
        self::assertInstanceOf(AudioFolderMoved::class, $this->events->messages[0]);
    }

    public function testMoveIntoParentWithSameNamedChildIsRejected(): void
    {
        $departures = $this->create('Вылеты');
        $this->create('SU 1234', $departures->id);
        $loose = $this->create('SU 1234');

        $this->expectException(DuplicateAudioFolderException::class);
        $this->update($loose->id, 'SU 1234', $departures->id);
    }

    public function testRenameKeepingOwnNameIsNotADuplicate(): void
    {
        $folder = $this->create('Музыка');

        $result = $this->update($folder->id, 'музыка', null);

        self::assertSame('музыка', $result->name);
    }

    public function testDeleteRejectsFolderWithSubfolders(): void
    {
        $departures = $this->create('Вылеты');
        $this->create('SU 1234', $departures->id);

        $this->expectException(AudioFolderNotEmptyException::class);
        $this->delete($departures->id, $this->assetsStub(activeCount: 0));
    }

    public function testDeleteRejectsFolderWithActiveAssets(): void
    {
        $folder = $this->create('Гонги');

        $this->expectException(AudioFolderNotEmptyException::class);
        $this->delete($folder->id, $this->assetsStub(activeCount: 1));
    }

    public function testDeleteMovesInactiveAssetsToRoot(): void
    {
        $folder = $this->create('Гонги');
        $inactive = AudioAsset::upload('gong.wav', LanguageCode::fromString('ru'), 'key', 'audio/wav', 10, null, Uuid::fromString($folder->id));
        $inactive->deactivate();
        $inactive->pullEvents();
        $this->events->messages = [];

        $assets = $this->createMock(AudioAssetRepositoryInterface::class);
        $assets->method('countActiveInFolder')->willReturn(0);
        $assets->method('findInactiveInFolder')->willReturn([$inactive]);
        $assets->expects(self::once())->method('save')->with($inactive);

        $this->delete($folder->id, $assets);

        self::assertNull($inactive->getFolderId());
        self::assertNull($this->folders->findById(Uuid::fromString($folder->id)));
        self::assertInstanceOf(AudioAssetUpdated::class, $this->events->messages[0]);
        self::assertInstanceOf(AudioFolderDeleted::class, $this->events->messages[1]);
    }

    private function create(string $name, ?string $parentId = null): AudioFolderResult
    {
        $handler = new CreateAudioFolderHandler(
            $this->folders,
            new AudioFolderResolver($this->folders),
            new AudioFolderHierarchy($this->folders),
            $this->events,
        );

        return $handler(new CreateAudioFolderCommand($name, $parentId));
    }

    private function update(string $id, string $name, ?string $parentId): AudioFolderResult
    {
        $handler = new UpdateAudioFolderHandler(
            $this->folders,
            new AudioFolderResolver($this->folders),
            new AudioFolderHierarchy($this->folders),
            $this->events,
        );

        return $handler(new UpdateAudioFolderCommand($id, $name, $parentId));
    }

    private function delete(string $id, AudioAssetRepositoryInterface $assets): void
    {
        $handler = new DeleteAudioFolderHandler(
            $this->folders,
            $assets,
            new AudioFolderResolver($this->folders),
            $this->events,
        );

        $handler(new DeleteAudioFolderCommand($id));
    }

    private function assetsStub(int $activeCount): AudioAssetRepositoryInterface
    {
        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('countActiveInFolder')->willReturn($activeCount);
        $assets->method('findInactiveInFolder')->willReturn([]);

        return $assets;
    }
}
