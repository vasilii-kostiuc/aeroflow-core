<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Service;

use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use Symfony\Component\Uid\Uuid;

final readonly class FlightDefinitionUniquenessChecker
{
    public function __construct(
        private FlightDefinitionRepositoryInterface $repository,
    ) {
    }

    public function ensureUnique(
        FlightNumber $flightNumber,
        FlightDirection $direction,
        AirportCode $originAirportCode,
        AirportCode $destinationAirportCode,
        ?Uuid $excludeId = null,
    ): void {
        if ($this->repository->hasConflictingDefinition(
            $flightNumber,
            $direction,
            $originAirportCode,
            $destinationAirportCode,
            $excludeId,
        )) {
            throw DuplicateFlightDefinitionException::create();
        }
    }
}
