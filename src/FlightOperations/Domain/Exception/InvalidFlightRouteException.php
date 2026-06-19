<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidFlightRouteException extends DomainException
{
    public static function sameAirports(string $airportCode): self
    {
        return new self(sprintf('Origin and destination airport must differ; both are "%s".', $airportCode));
    }
}
