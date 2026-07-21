<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\GenerateAudioAsset;

final readonly class GenerateAudioAssetCommand
{
    public function __construct(
        public string $text,
        public string $languageCode,
        public ?string $voice = null,
    ) {
    }
}
