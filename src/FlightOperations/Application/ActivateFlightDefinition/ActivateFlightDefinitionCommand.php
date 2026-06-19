<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ActivateFlightDefinition;

final readonly class ActivateFlightDefinitionCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
