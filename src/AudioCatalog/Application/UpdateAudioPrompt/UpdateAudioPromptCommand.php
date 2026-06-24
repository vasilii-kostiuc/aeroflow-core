<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UpdateAudioPrompt;

final readonly class UpdateAudioPromptCommand
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $value,
        public string $languageCode,
        public string $audioAssetId,
    ) {
    }
}
