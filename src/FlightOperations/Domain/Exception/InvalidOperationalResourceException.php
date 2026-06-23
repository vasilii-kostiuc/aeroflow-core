<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidOperationalResourceException extends DomainException
{
    public static function invalidCode(string $value): self
    {
        return new self(sprintf('Invalid operational resource code "%s".', $value));
    }

    public static function emptyName(): self
    {
        return new self('Operational resource display name cannot be empty.');
    }

    public static function invalidSortOrder(): self
    {
        return new self('Operational resource sort order must be positive.');
    }
}
