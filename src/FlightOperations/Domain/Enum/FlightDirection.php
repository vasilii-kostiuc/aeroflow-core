<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Enum;

use App\FlightOperations\Domain\Exception\InvalidFlightDirectionException;

enum FlightDirection: string
{
    case Departure = 'departure';
    case Arrival = 'arrival';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw InvalidFlightDirectionException::forValue($value);
    }
}
