<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\FlightOperations;

interface OperationalResourceLookupInterface
{
    /**
     * @param list<string> $ids
     *
     * @return list<OperationalResourceSnapshot>
     */
    public function resolveActiveCheckInCounters(array $ids): array;

    public function resolveActiveGate(string $id): ?OperationalResourceSnapshot;
}
