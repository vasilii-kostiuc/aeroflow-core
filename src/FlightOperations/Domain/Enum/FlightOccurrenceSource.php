<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Enum;

enum FlightOccurrenceSource: string
{
    case Manual = 'manual';
    case Schedule = 'schedule';
}
