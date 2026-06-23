<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\FlightOperations;

use App\Announcements\Domain\Enum\FlightDirection;

final readonly class FlightDefinitionSnapshot
{
    public function __construct(
        public bool $active,
        public FlightDirection $direction,
    ) {
    }
}
