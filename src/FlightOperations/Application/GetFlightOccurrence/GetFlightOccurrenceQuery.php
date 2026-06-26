<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\GetFlightOccurrence;

final readonly class GetFlightOccurrenceQuery
{
    public function __construct(public string $id)
    {
    }
}
