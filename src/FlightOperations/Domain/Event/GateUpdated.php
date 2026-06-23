<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Event;

use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class GateUpdated implements DomainEvent
{
    public function __construct(public string $id, public string $code, public DateTimeImmutable $occurredAt)
    {
    }
}
