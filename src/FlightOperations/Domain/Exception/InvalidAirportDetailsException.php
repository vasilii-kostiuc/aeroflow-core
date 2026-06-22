<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAirportDetailsException extends DomainException
{
    public static function empty(string $field): self
    {
        return new self($field.' must not be empty.');
    }

    public static function invalidCountryCode(): self
    {
        return new self('Country code must contain exactly two Latin letters.');
    }
}
