<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAudioPromptException extends DomainException
{
    public static function invalidValue(string $value): self
    {
        return new self(sprintf('Invalid audio prompt value "%s".', $value));
    }
}
