<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAudioFolderException extends DomainException
{
    public static function emptyName(): self
    {
        return new self('Folder name cannot be empty.');
    }

    public static function nameTooLong(int $max): self
    {
        return new self(sprintf('Folder name cannot be longer than %d characters.', $max));
    }

    public static function slashInName(): self
    {
        return new self('Folder name cannot contain "/".');
    }

    public static function cycle(): self
    {
        return new self('A folder cannot be moved into itself or one of its subfolders.');
    }

    public static function tooDeep(int $max): self
    {
        return new self(sprintf('Folders cannot be nested deeper than %d levels.', $max));
    }
}
