<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\MoveAudioAssets;

/** Move several assets into one folder (null = «Без папки») in one transaction. */
final readonly class MoveAudioAssetsCommand
{
    /**
     * @param list<string> $assetIds
     */
    public function __construct(
        public array $assetIds,
        public ?string $folderId,
    ) {
    }
}
