<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\DeactivateFlightDefinition;

final readonly class DeactivateFlightDefinitionCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
