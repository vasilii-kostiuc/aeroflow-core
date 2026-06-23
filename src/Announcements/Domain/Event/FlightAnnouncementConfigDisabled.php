<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class FlightAnnouncementConfigDisabled implements DomainEvent
{
    public function __construct(
        public string $configId,
        public string $flightDefinitionId,
        public string $announcementType,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
