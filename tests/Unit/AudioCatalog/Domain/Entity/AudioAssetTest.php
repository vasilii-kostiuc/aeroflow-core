<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Enum\AudioAssetSource;
use App\AudioCatalog\Domain\Event\AudioAssetGenerated;
use App\AudioCatalog\Domain\Event\AudioAssetUploaded;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;

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
}
