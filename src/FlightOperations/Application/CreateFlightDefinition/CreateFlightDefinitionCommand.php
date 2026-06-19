<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\CreateFlightDefinition;

final readonly class CreateFlightDefinitionCommand
{
    public function __construct(
        public string $flightNumber,
        public string $direction,
        public string $originAirportCode,
        public string $destinationAirportCode,
    ) {
    }
}
