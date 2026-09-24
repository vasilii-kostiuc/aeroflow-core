<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAudioAssetException extends DomainException
{
    public static function emptyTitle(): self
    {
        return new self('Audio asset title cannot be empty.');
    }

    public static function titleTooLong(int $max): self
    {
        return new self(sprintf('Audio asset title cannot be longer than %d characters.', $max));
    }

    public static function nothingToMove(): self
    {
        return new self('Choose at least one audio asset to move.');
    }

    public static function tooManyToMove(int $max): self
    {
        return new self(sprintf('At most %d audio assets can be moved at once.', $max));
    }
}
