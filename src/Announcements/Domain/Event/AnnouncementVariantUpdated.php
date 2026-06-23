<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AnnouncementVariantUpdated implements DomainEvent
{
    public function __construct(
        public string $configId,
        public string $variantId,
        public string $languageCode,
        public string $contentModel,
        /** @var list<string> */
        public array $changedFields,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
