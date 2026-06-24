<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\ChangeAudioPromptStatus;

final readonly class ChangeAudioPromptStatusCommand
{
    public function __construct(
        public string $id,
        public bool $active,
    ) {
    }
}
