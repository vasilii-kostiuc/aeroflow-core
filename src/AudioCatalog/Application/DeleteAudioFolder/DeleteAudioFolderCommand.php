<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\DeleteAudioFolder;

final readonly class DeleteAudioFolderCommand
{
    public function __construct(public string $id)
    {
    }
}
