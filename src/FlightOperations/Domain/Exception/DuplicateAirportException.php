<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateAirportException extends DomainException
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('Airport with code "%s" already exists.', $code));
    }
}
