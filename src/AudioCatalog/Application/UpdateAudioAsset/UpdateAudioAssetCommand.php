<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UpdateAudioAsset;

/** Full replace of the asset's library details: title and folder (null = «Без папки»). */
final readonly class UpdateAudioAssetCommand
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $folderId,
    ) {
    }
}
