<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ChangeAirportStatus;

final readonly class ChangeAirportStatusCommand
{
    public function __construct(public string $id, public bool $active)
    {
    }
}
