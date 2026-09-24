<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateAudioFolderException extends DomainException
{
    public static function withName(string $name): self
    {
        return new self(sprintf('A folder named "%s" already exists here.', $name));
    }
}
