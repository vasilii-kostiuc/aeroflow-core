<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Enum;

enum FlightOccurrenceStatus: string
{
    case Scheduled = 'scheduled';
    case CheckInOpen = 'check_in_open';
    case CheckInClosed = 'check_in_closed';
    case Boarding = 'boarding';
    case ArrivalAnnounced = 'arrival_announced';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
