<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class AudioFolderNotEmptyException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Audio folder "%s" still contains subfolders or active audio assets.', $id));
    }
}
