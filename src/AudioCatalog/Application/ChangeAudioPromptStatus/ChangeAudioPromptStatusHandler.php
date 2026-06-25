<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\ChangeAudioPromptStatus;

use App\AudioCatalog\Application\AudioPromptResult;
use App\AudioCatalog\Domain\Exception\AudioPromptNotFoundException;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangeAudioPromptStatusHandler
{
    public function __construct(
        private AudioPromptRepositoryInterface $prompts,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(ChangeAudioPromptStatusCommand $command): AudioPromptResult
    {
        if (!Uuid::isValid($command->id)) {
            throw AudioPromptNotFoundException::withId($command->id);
        }

        $prompt = $this->prompts->findById(Uuid::fromString($command->id))
            ?? throw AudioPromptNotFoundException::withId($command->id);

        $command->active ? $prompt->activate() : $prompt->deactivate();
        $this->prompts->save($prompt);

        $this->events->publish(...$prompt->pullEvents());

        return AudioPromptResult::fromEntity($prompt);
    }
}
