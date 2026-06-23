<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum FlightAnnouncementType: string
{
    case CheckInOpening = 'check_in_opening';
    case CheckInContinuation = 'check_in_continuation';
    case CheckInClosing = 'check_in_closing';
    case BoardingInvitation = 'boarding_invitation';
    case Arrival = 'arrival';

    public function isCompatibleWith(FlightDirection $direction): bool
    {
        return match ($this) {
            self::Arrival => FlightDirection::Arrival === $direction,
            self::CheckInOpening,
            self::CheckInContinuation,
            self::CheckInClosing,
            self::BoardingInvitation => FlightDirection::Departure === $direction,
        };
    }

    public function requiresRepeatRule(): bool
    {
        return self::CheckInContinuation === $this;
    }
}
