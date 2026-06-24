<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\ListAudioPrompts;

use App\AudioCatalog\Application\AudioPromptResult;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListAudioPromptsHandler
{
    public function __construct(private AudioPromptRepositoryInterface $prompts)
    {
    }

    /**
     * @return list<AudioPromptResult>
     */
    public function __invoke(ListAudioPromptsQuery $query): array
    {
        $prompts = $this->prompts->findAll(
            $query->kind === null ? null : AudioPromptKind::from($query->kind),
            $query->value,
            $query->languageCode,
            $query->active,
        );

        return array_map(AudioPromptResult::fromEntity(...), $prompts);
    }
}
