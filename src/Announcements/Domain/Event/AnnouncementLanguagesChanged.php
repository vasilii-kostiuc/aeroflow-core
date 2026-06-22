<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AnnouncementLanguagesChanged implements DomainEvent
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        public string $announcementId,
        public array $languages,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
