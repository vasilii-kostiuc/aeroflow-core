<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\AudioLibrary;

/**
 * What the organizer will do with one asset: move it to a folder path (names from
 * the top level down) and/or give it a better title. Null means "leave as is".
 */
final readonly class PlannedAsset
{
    /**
     * @param list<string>|null $folderPath
     */
    public function __construct(
        public string $assetId,
        public string $currentTitle,
        public ?string $currentFolderId,
        public ?array $folderPath,
        public ?string $newTitle,
    ) {
    }
}
