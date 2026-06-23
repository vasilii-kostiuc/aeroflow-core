<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class AudioPromptCreated implements DomainEvent
{
    public function __construct(public string $id, public string $kind, public string $value, public string $languageCode, public DateTimeImmutable $occurredAt)
    {
    }
}
