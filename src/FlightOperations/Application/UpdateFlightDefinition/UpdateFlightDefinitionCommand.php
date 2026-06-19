<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\UpdateFlightDefinition;

final readonly class UpdateFlightDefinitionCommand
{
    public function __construct(
        public string $id,
        public string $flightNumber,
        public string $direction,
        public string $originAirportCode,
        public string $destinationAirportCode,
    ) {
    }
}
