<?php

namespace App\Shared\Domain;

use App\Shared\Domain\DomainEvent;

abstract class AggregateRoot
{
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
