<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Enum\AudioAssetSource;
use App\AudioCatalog\Domain\Event\AudioAssetGenerated;
use App\AudioCatalog\Domain\Event\AudioAssetUpdated;
use App\AudioCatalog\Domain\Event\AudioAssetUploaded;
use App\AudioCatalog\Domain\Exception\InvalidAudioAssetException;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AudioAssetTest extends TestCase
{
    public function testUploadedAssetHasUploadedSource(): void
    {
        $asset = AudioAsset::upload('gong.mp3', LanguageCode::fromString('en'), 'key', 'audio/mpeg', 2048);

        self::assertSame(AudioAssetSource::Uploaded, $asset->getSource());
        self::assertNull($asset->getTtsVoice());
        self::assertNull($asset->getTtsModelVersion());
        self::assertNull($asset->getTtsTextHash());

        $events = $asset->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioAssetUploaded::class, $events[0]);
    }

    public function testGeneratedAssetCarriesSourceCacheAttributesAndEvent(): void
    {
        $asset = AudioAsset::generate(
            'tts-ru-boarding.wav',
            LanguageCode::fromString('ru'),
            'storage-key',
            'audio/wav',
            4096,
            'text-hash',
            'dmitri',
            'v1',
        );

        self::assertSame(AudioAssetSource::Generated, $asset->getSource());
        self::assertTrue($asset->isActive());
        self::assertSame('text-hash', $asset->getTtsTextHash());
        self::assertSame('dmitri', $asset->getTtsVoice());
        self::assertSame('v1', $asset->getTtsModelVersion());

        $events = $asset->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioAssetGenerated::class, $events[0]);
        self::assertSame('dmitri', $events[0]->voice);
        self::assertSame('v1', $events[0]->modelVersion);
    }

    public function testDeactivateIsIdempotent(): void
    {
        $asset = AudioAsset::generate('a.wav', LanguageCode::fromString('ru'), 'k', 'audio/wav', 10, 'h', 'v', 'v1');

        $asset->deactivate();
        self::assertFalse($asset->isActive());

        // Second call must not flip state back or throw.
        $asset->deactivate();
        self::assertFalse($asset->isActive());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function fileNameTitles(): iterable
    {
        yield 'dashes and extension' => ['gate-a12-en.wav', 'gate a12 en'];
        yield 'underscores and spaces' => ['Гонг__обычный  1.mp3', 'Гонг обычный 1'];
        yield 'only extension part is stripped' => ['v1.2-final.ogg', 'v1.2 final'];
        yield 'nothing left falls back to name' => ['---.wav', '---.wav'];
    }

    #[DataProvider('fileNameTitles')]
    public function testTitleDefaultsToFileNameWithoutExtension(string $name, string $expectedTitle): void
    {
        $asset = AudioAsset::upload($name, LanguageCode::fromString('en'), 'key', 'audio/wav', 10);

        self::assertSame($expectedTitle, $asset->getTitle());
        self::assertNull($asset->getFolderId());
    }

    public function testExplicitTitleAndFolderAreKept(): void
    {
        $folderId = Uuid::v7();
        $asset = AudioAsset::upload('gate-a12-en.wav', LanguageCode::fromString('en'), 'key', 'audio/wav', 10, ' Выход  A12 ', $folderId);

        self::assertSame('Выход A12', $asset->getTitle());
        self::assertSame('gate-a12-en.wav', $asset->getName());
        self::assertTrue($folderId->equals($asset->getFolderId()));
    }

    public function testRejectsEmptyTitle(): void
    {
        $this->expectException(InvalidAudioAssetException::class);
        AudioAsset::upload('a.wav', LanguageCode::fromString('en'), 'key', 'audio/wav', 10, '   ');
    }

    public function testUpdateDetailsIsIdempotentAndKeepsName(): void
    {
        $asset = AudioAsset::upload('gate-a12-en.wav', LanguageCode::fromString('en'), 'key', 'audio/wav', 10);
        $asset->pullEvents();
        $folderId = Uuid::v7();

        self::assertTrue($asset->updateDetails('Выход A12', $folderId));
        self::assertFalse($asset->updateDetails('Выход A12', Uuid::fromString($folderId->toRfc4122())));

        $events = $asset->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AudioAssetUpdated::class, $events[0]);
        self::assertSame('Выход A12', $events[0]->title);
        self::assertSame($folderId->toRfc4122(), $events[0]->folderId);
        self::assertSame('gate-a12-en.wav', $asset->getName());

        self::assertTrue($asset->updateDetails('Выход A12', null));
        self::assertNull($asset->getFolderId());
    }

    public function testUpdateDetailsRejectsTooLongTitle(): void
    {
        $asset = AudioAsset::upload('a.wav', LanguageCode::fromString('en'), 'key', 'audio/wav', 10);

        $this->expectException(InvalidAudioAssetException::class);
        $asset->updateDetails(str_repeat('x', AudioAsset::MAX_TITLE_LENGTH + 1), null);
    }
}
