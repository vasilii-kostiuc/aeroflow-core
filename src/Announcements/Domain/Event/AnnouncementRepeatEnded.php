<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * The repeat series of a continuation announcement has been ended (task 020),
 * because check-in closed. Recorded only on the first transition, so a repeated end
 * never duplicates the outbound StopAnnouncementRepeat. Not a cancellation: the
 * announcement itself stays Prepared.
 */
final readonly class AnnouncementRepeatEnded implements DomainEvent
{
    public function __construct(
        public string $announcementId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
