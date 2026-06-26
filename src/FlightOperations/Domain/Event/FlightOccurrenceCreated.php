<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class FlightOccurrenceCreated implements DomainEvent
{
    public function __construct(
        public string $flightOccurrenceId,
        public string $flightDefinitionId,
        public string $operationalDate,
        public int $sequenceNumber,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
