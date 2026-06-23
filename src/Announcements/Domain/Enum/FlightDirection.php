<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum FlightDirection: string
{
    case Departure = 'departure';
    case Arrival = 'arrival';
}
