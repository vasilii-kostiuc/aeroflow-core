<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\CreateAirport;

final readonly class CreateAirportCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public string $cityName,
        public string $countryCode,
    ) {
    }
}
