<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AudioAssetUploaded implements DomainEvent
{
    public function __construct(
        public string $audioAssetId,
        public string $name,
        public string $languageCode,
        public string $mimeType,
        public int $sizeBytes,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
