<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class CheckInCounterDeactivated implements DomainEvent
{
    public function __construct(public string $id, public DateTimeImmutable $occurredAt)
    {
    }
}
