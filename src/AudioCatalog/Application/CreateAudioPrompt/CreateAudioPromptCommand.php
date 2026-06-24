<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\CreateAudioPrompt;

final readonly class CreateAudioPromptCommand
{
    public function __construct(
        public string $kind,
        public string $value,
        public string $languageCode,
        public string $audioAssetId,
    ) {
    }
}
