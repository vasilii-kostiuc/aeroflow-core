<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum AnnouncementType: string
{
    case CheckInOpening = 'check_in_opening';
    case CheckInClosing = 'check_in_closing';
    case BoardingInvitation = 'boarding_invitation';
    case Arrival = 'arrival';

    public function requiresCheckInCounters(): bool
    {
        return self::CheckInOpening === $this;
    }

    public function requiresGate(): bool
    {
        return self::BoardingInvitation === $this;
    }
}
