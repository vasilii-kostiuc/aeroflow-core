<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AnnouncementVariantAdded implements DomainEvent
{
    public function __construct(
        public string $configId,
        public string $variantId,
        public string $languageCode,
        public string $sourceType,
        public bool $enabled,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
