<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UpdateAudioPrompt;

use App\AudioCatalog\Application\AudioPromptResult;
use App\AudioCatalog\Application\Support\AudioPromptAssetGuard;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Exception\AudioPromptNotFoundException;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateAudioPromptHandler
{
    public function __construct(
        private AudioPromptRepositoryInterface $prompts,
        private AudioPromptAssetGuard $assetGuard,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateAudioPromptCommand $command): AudioPromptResult
    {
        $this->assetGuard->assertAvailable($command->audioAssetId);

        if (!Uuid::isValid($command->id)) {
            throw AudioPromptNotFoundException::withId($command->id);
        }

        $prompt = $this->prompts->findById(Uuid::fromString($command->id))
            ?? throw AudioPromptNotFoundException::withId($command->id);

        $prompt->update(
            AudioPromptKind::from($command->kind),
            $command->value,
            LanguageCode::fromString($command->languageCode),
            Uuid::fromString($command->audioAssetId),
        );
        $this->prompts->save($prompt);

        foreach ($prompt->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return AudioPromptResult::fromEntity($prompt);
    }
}
