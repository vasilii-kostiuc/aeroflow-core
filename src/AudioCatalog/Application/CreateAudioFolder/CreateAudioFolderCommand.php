<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\CreateAudioFolder;

final readonly class CreateAudioFolderCommand
{
    public function __construct(
        public string $name,
        public ?string $parentId = null,
    ) {
    }
}
