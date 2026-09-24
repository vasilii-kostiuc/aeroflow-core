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

    /**
     * Statuses of a run that has been started but has not reached the final status
     * of its lifecycle. Such a run outlives its operational day: a check-in opened
     * before midnight is closed and boarded after it (task 027). An arrival has no
     * intermediate status, and a scheduled run has not been acted upon yet.
     *
     * @return list<self>
     */
    public static function inProgress(): array
    {
        return [self::CheckInOpen, self::CheckInClosed];
    }
}
