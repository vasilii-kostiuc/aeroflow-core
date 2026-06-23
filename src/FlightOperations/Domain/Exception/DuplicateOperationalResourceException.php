<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateOperationalResourceException extends DomainException
{
    public static function forTypeAndCode(string $type, string $code): self
    {
        return new self(sprintf('%s with code "%s" already exists.', $type, $code));
    }
}
