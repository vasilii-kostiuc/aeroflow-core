<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\GetFlightDefinition;

final readonly class GetFlightDefinitionQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}
