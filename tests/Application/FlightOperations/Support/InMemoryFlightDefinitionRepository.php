<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations\Support;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use Symfony\Component\Uid\Uuid;

final class InMemoryFlightDefinitionRepository implements FlightDefinitionRepositoryInterface
{
    /**
     * @var array<string, FlightDefinition>
     */
    private array $items = [];

    public int $saveCalls = 0;

    public function save(FlightDefinition $flightDefinition): void
    {
        ++$this->saveCalls;
        $this->items[$flightDefinition->getId()->toRfc4122()] = $flightDefinition;
    }

    public function findById(Uuid $id): ?FlightDefinition
    {
        return $this->items[$id->toRfc4122()] ?? null;
    }

    public function hasConflictingDefinition(
        FlightNumber $flightNumber,
        FlightDirection $direction,
        AirportCode $originAirportCode,
        AirportCode $destinationAirportCode,
        ?Uuid $excludeId = null,
    ): bool {
        foreach ($this->items as $item) {
            if ($excludeId !== null && $item->getId()->equals($excludeId)) {
                continue;
            }

            if (
                $item->getFlightNumber()->equals($flightNumber)
                && $item->getDirection() === $direction
                && $item->getOriginAirportCode()->equals($originAirportCode)
                && $item->getDestinationAirportCode()->equals($destinationAirportCode)
            ) {
                return true;
            }
        }

        return false;
    }

    public function add(FlightDefinition $flightDefinition): void
    {
        $this->items[$flightDefinition->getId()->toRfc4122()] = $flightDefinition;
    }
}
