<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class FlightDefinitionUpdated implements DomainEvent
{
    public function __construct(
        public string $flightDefinitionId,
        public string $flightNumber,
        public string $direction,
        public string $originAirportCode,
        public string $destinationAirportCode,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
