<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateFlightOccurrenceException extends DomainException
{
    public static function forBusinessKey(): self
    {
        return new self('A flight occurrence for the same flight, operational date, source and sequence already exists.');
    }
}
