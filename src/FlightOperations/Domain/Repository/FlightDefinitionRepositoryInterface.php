<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Repository;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use Symfony\Component\Uid\Uuid;

interface FlightDefinitionRepositoryInterface
{
    public function save(FlightDefinition $flightDefinition): void;

    public function findById(Uuid $id): ?FlightDefinition;

    public function hasConflictingDefinition(
        FlightNumber $flightNumber,
        FlightDirection $direction,
        AirportCode $originAirportCode,
        AirportCode $destinationAirportCode,
        ?Uuid $excludeId = null,
    ): bool;
}
