<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AudioFolderRenamed implements DomainEvent
{
    public function __construct(
        public string $folderId,
        public string $name,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
