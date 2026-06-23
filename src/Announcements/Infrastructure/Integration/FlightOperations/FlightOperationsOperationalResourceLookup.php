<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\FlightOperations;

use App\Announcements\Application\Port\FlightOperations\OperationalResourceLookupInterface;
use App\Announcements\Application\Port\FlightOperations\OperationalResourceSnapshot;
use App\FlightOperations\Domain\Repository\CheckInCounterRepositoryInterface;
use App\FlightOperations\Domain\Repository\GateRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FlightOperationsOperationalResourceLookup implements OperationalResourceLookupInterface
{
    public function __construct(
        private CheckInCounterRepositoryInterface $counters,
        private GateRepositoryInterface $gates,
    ) {
    }

    /** @return list<OperationalResourceSnapshot> */
    public function resolveActiveCheckInCounters(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (!Uuid::isValid($id)) {
                continue;
            }
            $counter = $this->counters->findById(Uuid::fromString($id));
            if ($counter !== null && $counter->isActive()) {
                $result[] = new OperationalResourceSnapshot($id, $counter->getCode()->toString());
            }
        }

        return $result;
    }

    public function resolveActiveGate(string $id): ?OperationalResourceSnapshot
    {
        if (!Uuid::isValid($id)) {
            return null;
        }
        $gate = $this->gates->findById(Uuid::fromString($id));

        return $gate !== null && $gate->isActive()
            ? new OperationalResourceSnapshot($id, $gate->getCode()->toString())
            : null;
    }
}
