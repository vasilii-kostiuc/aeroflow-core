<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Event\AudioFolderCreated;
use App\AudioCatalog\Domain\Event\AudioFolderDeleted;
use App\AudioCatalog\Domain\Event\AudioFolderMoved;
use App\AudioCatalog\Domain\Event\AudioFolderRenamed;
use App\AudioCatalog\Domain\Exception\InvalidAudioFolderException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AudioFolderTest extends TestCase
{
    public function testCreateNormalizesNameAndRecordsEvent(): void
    {
        $parentId = Uuid::v7();
        $folder = AudioFolder::create('  SU   1234 ', $parentId);

        self::assertSame('SU 1234', $folder->getName());
        self::assertSame('su 1234', $folder->getNameKey());
        self::assertTrue($parentId->equals($folder->getParentId()));

        $events = $folder->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioFolderCreated::class, $events[0]);
        self::assertSame($parentId->toRfc4122(), $events[0]->parentId);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'empty' => ['   '];
        yield 'too long' => [str_repeat('я', AudioFolder::MAX_NAME_LENGTH + 1)];
        yield 'slash' => ['Вылеты/SU 1234'];
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidName(string $name): void
    {
        $this->expectException(InvalidAudioFolderException::class);
        AudioFolder::create($name, null);
    }

    public function testRenameIsIdempotent(): void
    {
        $folder = AudioFolder::create('Гонги', null);
        $folder->pullEvents();

        self::assertFalse($folder->rename(' Гонги '));
        self::assertSame([], $folder->pullEvents());

        self::assertTrue($folder->rename('Гонги и сигналы'));
        self::assertSame('гонги и сигналы', $folder->getNameKey());
        $events = $folder->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioFolderRenamed::class, $events[0]);
    }

    public function testMoveIsIdempotentAndRecordsEvent(): void
    {
        $folder = AudioFolder::create('SU 1234', null);
        $folder->pullEvents();

        self::assertFalse($folder->moveTo(null));
        self::assertSame([], $folder->pullEvents());

        $parentId = Uuid::v7();
        self::assertTrue($folder->moveTo($parentId));
        self::assertFalse($folder->moveTo(Uuid::fromString($parentId->toRfc4122())));

        $events = $folder->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioFolderMoved::class, $events[0]);
        self::assertSame($parentId->toRfc4122(), $events[0]->parentId);
    }

    public function testCannotMoveIntoItself(): void
    {
        $folder = AudioFolder::create('Вылеты', null);

        $this->expectException(InvalidAudioFolderException::class);
        $folder->moveTo($folder->getId());
    }

    public function testDeleteRecordsEvent(): void
    {
        $folder = AudioFolder::create('Музыка', null);
        $folder->pullEvents();

        $folder->delete();

        $events = $folder->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioFolderDeleted::class, $events[0]);
    }
}
