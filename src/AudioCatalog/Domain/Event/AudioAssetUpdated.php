<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AudioAssetUpdated implements DomainEvent
{
    public function __construct(
        public string $audioAssetId,
        public string $title,
        public ?string $folderId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
