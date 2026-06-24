<?php

declare(strict_types=1);

namespace App\Tests\Application\AudioCatalog;

use App\AudioCatalog\Application\AudioPromptResult;
use App\AudioCatalog\Application\CreateAudioPrompt\CreateAudioPromptCommand;
use App\AudioCatalog\Application\CreateAudioPrompt\CreateAudioPromptHandler;
use App\AudioCatalog\Application\Support\AudioPromptAssetGuard;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Entity\AudioPrompt;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Event\AudioPromptCreated;
use App\AudioCatalog\Domain\Exception\AudioPromptAssetUnavailableException;
use App\AudioCatalog\Domain\Exception\DuplicateAudioPromptException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class CreateAudioPromptHandlerTest extends TestCase
{
    public function testRejectsUnavailableAssetBeforePersisting(): void
    {
        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn(null);

        $prompts = $this->createMock(AudioPromptRepositoryInterface::class);
        $prompts->expects(self::never())->method('findActive');
        $prompts->expects(self::never())->method('save');

        $handler = new CreateAudioPromptHandler(
            $prompts,
            new AudioPromptAssetGuard($assets),
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(AudioPromptAssetUnavailableException::class);
        $handler(new CreateAudioPromptCommand('gate_code', 'A12', 'en', Uuid::v7()->toRfc4122()));
    }

    public function testRejectsDuplicateActivePrompt(): void
    {
        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn($this->activeAsset());

        $existing = AudioPrompt::create(AudioPromptKind::GateCode, 'A12', LanguageCode::fromString('en'), Uuid::v7());
        $prompts = $this->createMock(AudioPromptRepositoryInterface::class);
        $prompts->method('findActive')->willReturn($existing);
        $prompts->expects(self::never())->method('save');

        $handler = new CreateAudioPromptHandler(
            $prompts,
            new AudioPromptAssetGuard($assets),
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(DuplicateAudioPromptException::class);
        $handler(new CreateAudioPromptCommand('gate_code', 'A12', 'en', Uuid::v7()->toRfc4122()));
    }

    public function testCreatesPromptSavesAndPublishesEvent(): void
    {
        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn($this->activeAsset());

        $prompts = $this->createMock(AudioPromptRepositoryInterface::class);
        $prompts->method('findActive')->willReturn(null);
        $prompts->expects(self::once())->method('save');

        $published = [];
        $eventBus = $this->createStub(MessageBusInterface::class);
        $eventBus->method('dispatch')->willReturnCallback(function (object $event) use (&$published): Envelope {
            $published[] = $event;

            return new Envelope($event);
        });

        $handler = new CreateAudioPromptHandler($prompts, new AudioPromptAssetGuard($assets), $eventBus);

        $result = $handler(new CreateAudioPromptCommand('gate_code', 'A12', 'en', Uuid::v7()->toRfc4122()));

        self::assertInstanceOf(AudioPromptResult::class, $result);
        self::assertSame('gate_code', $result->kind);
        self::assertSame('A12', $result->value);
        self::assertTrue($result->active);
        self::assertCount(1, $published);
        self::assertInstanceOf(AudioPromptCreated::class, $published[0]);
    }

    private function activeAsset(): AudioAsset
    {
        return AudioAsset::upload('gong.mp3', LanguageCode::fromString('en'), 'storage-key', 'audio/mpeg', 1024);
    }
}
