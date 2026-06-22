<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AnnouncementCreated implements DomainEvent
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        public string $announcementId,
        public string $type,
        public string $flightDefinitionId,
        public array $languages,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
