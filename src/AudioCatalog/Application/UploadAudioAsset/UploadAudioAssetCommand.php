<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UploadAudioAsset;

final readonly class UploadAudioAssetCommand
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $languageCode,
        public int $sizeBytes,
    ) {
    }
}
