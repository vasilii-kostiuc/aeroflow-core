<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AnnouncementCancelled implements DomainEvent
{
    public function __construct(
        public string $announcementId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
