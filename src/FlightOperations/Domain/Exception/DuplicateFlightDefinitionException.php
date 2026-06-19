<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateFlightDefinitionException extends DomainException
{
    public static function create(): self
    {
        return new self('A flight definition with the same number, direction and route already exists.');
    }
}
