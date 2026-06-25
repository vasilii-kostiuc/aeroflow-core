<?php

declare(strict_types=1);

namespace App\Tests\Application\AudioCatalog;

use App\AudioCatalog\Application\ChangeAudioPromptStatus\ChangeAudioPromptStatusCommand;
use App\AudioCatalog\Application\ChangeAudioPromptStatus\ChangeAudioPromptStatusHandler;
use App\AudioCatalog\Domain\Entity\AudioPrompt;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Event\AudioPromptDeactivated;
use App\AudioCatalog\Domain\Exception\AudioPromptNotFoundException;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ChangeAudioPromptStatusHandlerTest extends TestCase
{
    public function testThrowsNotFoundForUnknownPrompt(): void
    {
        $prompts = $this->createMock(AudioPromptRepositoryInterface::class);
        $prompts->method('findById')->willReturn(null);
        $prompts->expects(self::never())->method('save');

        $handler = new ChangeAudioPromptStatusHandler($prompts, $this->createStub(DomainEventPublisher::class));

        $this->expectException(AudioPromptNotFoundException::class);
        $handler(new ChangeAudioPromptStatusCommand(Uuid::v7()->toRfc4122(), false));
    }

    public function testDeactivateSavesAndPublishesEvent(): void
    {
        $prompt = AudioPrompt::create(AudioPromptKind::GateCode, 'A12', LanguageCode::fromString('en'), Uuid::v7());
        $prompt->pullEvents();

        $prompts = $this->createMock(AudioPromptRepositoryInterface::class);
        $prompts->method('findById')->willReturn($prompt);
        $prompts->expects(self::once())->method('save');

        $events = new RecordingEventPublisher();

        $handler = new ChangeAudioPromptStatusHandler($prompts, $events);

        $result = $handler(new ChangeAudioPromptStatusCommand($prompt->getId()->toRfc4122(), false));

        self::assertFalse($result->active);
        self::assertCount(1, $events->messages);
        self::assertInstanceOf(AudioPromptDeactivated::class, $events->messages[0]);
    }
}
