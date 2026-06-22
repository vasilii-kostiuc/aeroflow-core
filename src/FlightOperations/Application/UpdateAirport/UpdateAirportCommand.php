<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\UpdateAirport;

final readonly class UpdateAirportCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $cityName,
        public string $countryCode,
    ) {
    }
}
