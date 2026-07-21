<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AudioAssetGenerated implements DomainEvent
{
    public function __construct(
        public string $audioAssetId,
        public string $name,
        public string $languageCode,
        public string $mimeType,
        public int $sizeBytes,
        public string $voice,
        public string $modelVersion,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
