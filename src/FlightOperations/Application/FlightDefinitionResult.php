<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use DateTimeImmutable;

final readonly class FlightDefinitionResult
{
    public function __construct(
        public string $id,
        public string $flightNumber,
        public string $direction,
        public string $originAirportCode,
        public string $destinationAirportCode,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(FlightDefinition $flightDefinition): self
    {
        return new self(
            id: $flightDefinition->getId()->toRfc4122(),
            flightNumber: $flightDefinition->getFlightNumber()->toString(),
            direction: $flightDefinition->getDirection()->value,
            originAirportCode: $flightDefinition->getOriginAirportCode()->toString(),
            destinationAirportCode: $flightDefinition->getDestinationAirportCode()->toString(),
            active: $flightDefinition->isActive(),
            createdAt: $flightDefinition->getCreatedAt()->format(DATE_RFC3339),
            updatedAt: $flightDefinition->getUpdatedAt()->format(DATE_RFC3339),
        );
    }

    /**
     * @param array{
     *     id: string,
     *     flight_number: string,
     *     direction: string,
     *     origin_airport_code: string,
     *     destination_airport_code: string,
     *     active: bool|int|string,
     *     created_at: string,
     *     updated_at: string
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            flightNumber: $row['flight_number'],
            direction: $row['direction'],
            originAirportCode: $row['origin_airport_code'],
            destinationAirportCode: $row['destination_airport_code'],
            active: filter_var($row['active'], FILTER_VALIDATE_BOOL),
            createdAt: new DateTimeImmutable($row['created_at'])->format(DATE_RFC3339),
            updatedAt: new DateTimeImmutable($row['updated_at'])->format(DATE_RFC3339),
        );
    }
}
