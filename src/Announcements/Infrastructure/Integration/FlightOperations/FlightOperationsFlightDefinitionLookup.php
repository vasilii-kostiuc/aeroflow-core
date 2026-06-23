<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\FlightOperations;

use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionSnapshot;
use App\Announcements\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FlightOperationsFlightDefinitionLookup implements FlightDefinitionLookupInterface
{
    public function __construct(private FlightDefinitionRepositoryInterface $repository)
    {
    }

    public function findById(Uuid $id): ?FlightDefinitionSnapshot
    {
        $flightDefinition = $this->repository->findById($id);
        if ($flightDefinition === null) {
            return null;
        }

        return new FlightDefinitionSnapshot(
            active: $flightDefinition->isActive(),
            direction: FlightDirection::from($flightDefinition->getDirection()->value),
        );
    }
}
