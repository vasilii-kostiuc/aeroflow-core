<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Application\Event\DomainEventPublisher;

/**
 * Buffers domain events raised inside a command bus transaction and releases
 * them only after the transaction commits, so a rolled-back use case never
 * publishes its events (e.g. an invalid FlightOccurrence transition must not
 * leave an AnnouncementCreated behind).
 *
 * Outside a transactional scope (depth 0 — e.g. a directory called directly from
 * a controller) events are dispatched immediately, preserving existing
 * behaviour. The scope is driven by DomainEventTransactionMiddleware.
 *
 * This is the in-process precursor to a transactional outbox.
 */
final class DeferredDomainEventPublisher implements DomainEventPublisher
{
    private int $depth = 0;

    /** @var list<object> */
    private array $buffer = [];

    public function __construct(private readonly MessengerDomainEventPublisher $publisher)
    {
    }

    public function publish(object ...$events): void
    {
        if ($this->depth === 0) {
            $this->publisher->publish(...$events);

            return;
        }

        foreach ($events as $event) {
            $this->buffer[] = $event;
        }
    }

    public function enter(): void
    {
        ++$this->depth;
    }

    /**
     * Leave the current scope; on the outermost scope, dispatch buffered events
     * (called after the transaction has committed).
     */
    public function commit(): void
    {
        if ($this->depth === 0) {
            return;
        }

        --$this->depth;
        if ($this->depth > 0) {
            return;
        }

        $events = $this->buffer;
        $this->buffer = [];
        if ($events !== []) {
            $this->publisher->publish(...$events);
        }
    }

    /**
     * Leave the current scope discarding buffered events on the outermost scope
     * (the transaction rolled back).
     */
    public function rollback(): void
    {
        if ($this->depth === 0) {
            return;
        }

        --$this->depth;
        if ($this->depth === 0) {
            $this->buffer = [];
        }
    }
}
