<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class SupplementaryTemplateUpdated implements DomainEvent
{
    public function __construct(
        public string $templateId,
        public string $name,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
