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
    /**
     * Airport-wide informational announcement, not tied to a flight (task 024).
     * The first non-flight announcement path; carries no counters, gate or flight.
     */
    case Supplementary = 'supplementary';
    /**
     * Airport-wide emergency announcement (task 026). Like Supplementary it carries
     * no flight; unlike it, launching one also activates emergency mode, so the
     * ordinary queue is suspended until the dispatcher lifts it.
     */
    case Emergency = 'emergency';

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

    /**
     * Whether this type belongs to a flight. The five flight types carry a
     * FlightDefinition; the airport-wide supplementary and emergency announcements
     * do not.
     */
    public function requiresFlight(): bool
    {
        return self::Supplementary !== $this && self::Emergency !== $this;
    }

    /**
     * Playback priority derived from the type (see "Приоритеты воспроизведения" in
     * README.md): an emergency announcement plays at 1000 — the reserved preemptive
     * threshold of playback — flight announcements at 100, supplementary at 50
     * (behind flight announcements, ahead of background music).
     */
    public function playbackPriority(): int
    {
        return match ($this) {
            self::Emergency => 1000,
            self::Supplementary => 50,
            default => 100,
        };
    }
}
