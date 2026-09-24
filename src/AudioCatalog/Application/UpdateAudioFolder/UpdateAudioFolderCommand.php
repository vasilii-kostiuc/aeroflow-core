<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UpdateAudioFolder;

/** Full replace of what an admin can change: the name and the parent (null = top level). */
final readonly class UpdateAudioFolderCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentId,
    ) {
    }
}
