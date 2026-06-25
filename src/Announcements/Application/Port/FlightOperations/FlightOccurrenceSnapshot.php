<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\FlightOperations;

final readonly class FlightOccurrenceSnapshot
{
    public function __construct(
        public string $id,
        public string $flightDefinitionId,
        public string $direction,
        public string $status,
    ) {
    }
}
