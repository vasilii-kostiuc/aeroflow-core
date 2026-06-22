<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\GetAirport;

final readonly class GetAirportQuery
{
    public function __construct(public string $id)
    {
    }
}
