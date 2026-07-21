<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum AnnouncementType: string
{
    case CheckInOpening = 'check_in_opening';
    case CheckInContinuation = 'check_in_continuation';
    case CheckInClosing = 'check_in_closing';
    case BoardingInvitation = 'boarding_invitation';
    case Arrival = 'arrival';

    public function requiresCheckInCounters(): bool
    {
        // Continuation repeats the "check-in continues at counters N" message, so it
        // carries counters just like the opening announcement.
        return self::CheckInOpening === $this || self::CheckInContinuation === $this;
    }

    public function requiresGate(): bool
    {
        return self::BoardingInvitation === $this;
    }

    /** Only the continuation repeats; its interval comes from the flight config. */
    public function isRepeatable(): bool
    {
        return self::CheckInContinuation === $this;
    }
}
