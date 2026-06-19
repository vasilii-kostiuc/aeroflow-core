<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAirportCodeException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid airport code "%s".', $value));
    }
}
