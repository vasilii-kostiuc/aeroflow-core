<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateAudioPromptException extends DomainException
{
    public static function forKey(string $kind, string $value, string $language): self
    {
        return new self(sprintf('Active audio prompt "%s/%s/%s" already exists.', $kind, $value, $language));
    }
}
