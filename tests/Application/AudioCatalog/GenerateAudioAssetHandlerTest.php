<?php

declare(strict_types=1);

namespace App\Tests\Application\AudioCatalog;

use App\AudioCatalog\Application\GenerateAudioAsset\GenerateAudioAssetCommand;
use App\AudioCatalog\Application\GenerateAudioAsset\GenerateAudioAssetHandler;
use App\AudioCatalog\Application\Port\Tts\SynthesizedAudio;
use App\AudioCatalog\Application\Port\Tts\TextToSpeechPort;
use App\AudioCatalog\Application\Port\Tts\TtsVoice;
use App\AudioCatalog\Application\Storage\AudioAssetStorageInterface;
use App\AudioCatalog\Application\Support\StoredAudioFile;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Enum\AudioAssetSource;
use App\AudioCatalog\Domain\Event\AudioAssetGenerated;
use App\AudioCatalog\Domain\Exception\InvalidSynthesisRequestException;
use App\AudioCatalog\Domain\Exception\TextToSpeechUnavailableException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;

final class GenerateAudioAssetHandlerTest extends TestCase
{
    public function testRejectsEmptyTextBeforeCallingTts(): void
    {
        $tts = $this->createMock(TextToSpeechPort::class);
        $tts->expects(self::never())->method('describeVoice');
        $tts->expects(self::never())->method('synthesize');

        $handler = new GenerateAudioAssetHandler(
            $this->createStub(AudioAssetRepositoryInterface::class),
            new StoredAudioFile($this->createStub(AudioAssetStorageInterface::class)),
            $tts,
            new RecordingEventPublisher(),
        );

        $this->expectException(InvalidSynthesisRequestException::class);
        $handler(new GenerateAudioAssetCommand('   ', 'ru'));
    }

    public function testReusesCachedAssetWithoutSynthesizing(): void
    {
        $cached = AudioAsset::generate('tts-ru-x.wav', LanguageCode::fromString('ru'), 'k', 'audio/wav', 10, 'h', 'dmitri', 'v1');
        $cached->pullEvents();

        $repository = $this->createMock(AudioAssetRepositoryInterface::class);
        $repository->method('findActiveGeneratedByContent')->willReturn([$cached]);
        $repository->expects(self::never())->method('save');

        $tts = $this->createMock(TextToSpeechPort::class);
        $tts->method('describeVoice')->willReturn(new TtsVoice('dmitri', 'ru', 'v1'));
        $tts->expects(self::never())->method('synthesize');

        $events = new RecordingEventPublisher();
        $handler = new GenerateAudioAssetHandler(
            $repository,
            new StoredAudioFile($this->createStub(AudioAssetStorageInterface::class)),
            $tts,
            $events,
        );

        $result = $handler(new GenerateAudioAssetCommand('привет', 'ru'));

        self::assertSame($cached->getId()->toRfc4122(), $result->id);
        self::assertSame('generated', $result->source);
        self::assertCount(0, $events->messages);
    }

    public function testGeneratesStoresAndPublishesOnCacheMiss(): void
    {
        $repository = $this->createMock(AudioAssetRepositoryInterface::class);
        $repository->method('findActiveGeneratedByContent')->willReturn([]);
        $repository->expects(self::once())->method('save');

        $storage = $this->createMock(AudioAssetStorageInterface::class);
        $storage->expects(self::once())->method('storeContents')->willReturn('generated-key.wav');

        $tts = $this->createMock(TextToSpeechPort::class);
        $tts->method('describeVoice')->willReturn(new TtsVoice('dmitri', 'ru', 'v1'));
        $tts->expects(self::once())->method('synthesize')->willReturn(new SynthesizedAudio('RIFFwav-bytes', 'audio/wav'));

        $events = new RecordingEventPublisher();
        $handler = new GenerateAudioAssetHandler($repository, new StoredAudioFile($storage), $tts, $events);

        $result = $handler(new GenerateAudioAssetCommand('Рейс 214', 'ru'));

        self::assertSame(AudioAssetSource::Generated->value, $result->source);
        self::assertSame('audio/wav', $result->mimeType);
        self::assertSame(strlen('RIFFwav-bytes'), $result->sizeBytes);
        self::assertCount(1, $events->messages);
        self::assertInstanceOf(AudioAssetGenerated::class, $events->messages[0]);
    }

    public function testModelVersionChangeGeneratesNewAndDeactivatesStale(): void
    {
        $stale = AudioAsset::generate('old.wav', LanguageCode::fromString('ru'), 'old-key', 'audio/wav', 10, 'h', 'dmitri', 'v0');
        $stale->pullEvents();

        $saved = [];
        $repository = $this->createStub(AudioAssetRepositoryInterface::class);
        $repository->method('findActiveGeneratedByContent')->willReturn([$stale]);
        $repository->method('save')->willReturnCallback(static function (AudioAsset $asset) use (&$saved): void {
            $saved[] = $asset;
        });

        $storage = $this->createStub(AudioAssetStorageInterface::class);
        $storage->method('storeContents')->willReturn('new-key.wav');

        $tts = $this->createMock(TextToSpeechPort::class);
        $tts->method('describeVoice')->willReturn(new TtsVoice('dmitri', 'ru', 'v1'));
        $tts->expects(self::once())->method('synthesize')->willReturn(new SynthesizedAudio('new-audio', 'audio/wav'));

        $handler = new GenerateAudioAssetHandler($repository, new StoredAudioFile($storage), $tts, new RecordingEventPublisher());

        $result = $handler(new GenerateAudioAssetCommand('Рейс 214', 'ru'));

        self::assertNotSame($stale->getId()->toRfc4122(), $result->id);
        self::assertFalse($stale->isActive(), 'stale asset from the old model must be deactivated');
        self::assertContains($stale, $saved, 'the deactivated stale asset must be persisted');
    }

    public function testTtsFailurePropagatesWithoutStoringOrSaving(): void
    {
        $repository = $this->createMock(AudioAssetRepositoryInterface::class);
        $repository->method('findActiveGeneratedByContent')->willReturn([]);
        $repository->expects(self::never())->method('save');

        $storage = $this->createMock(AudioAssetStorageInterface::class);
        $storage->expects(self::never())->method('storeContents');

        $tts = $this->createStub(TextToSpeechPort::class);
        $tts->method('describeVoice')->willReturn(new TtsVoice('dmitri', 'ru', 'v1'));
        $tts->method('synthesize')->willThrowException(TextToSpeechUnavailableException::synthesisFailed('down'));

        $handler = new GenerateAudioAssetHandler($repository, new StoredAudioFile($storage), $tts, new RecordingEventPublisher());

        $this->expectException(TextToSpeechUnavailableException::class);
        $handler(new GenerateAudioAssetCommand('Рейс 214', 'ru'));
    }
}
