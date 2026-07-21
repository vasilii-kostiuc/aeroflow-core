<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidSynthesisRequestException extends DomainException
{
    public static function emptyText(): self
    {
        return new self('Text to synthesize cannot be empty.');
    }

    public static function textTooLong(int $maxLength): self
    {
        return new self(sprintf('Text to synthesize cannot exceed %d characters.', $maxLength));
    }
}
