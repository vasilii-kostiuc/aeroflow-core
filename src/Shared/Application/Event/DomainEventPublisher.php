<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

/**
 * Publishes domain events raised by aggregates. Hides the underlying event
 * transport from the application layer and provides the single seam where a
 * transactional outbox can later replace direct dispatch.
 */
interface DomainEventPublisher
{
    public function publish(object ...$events): void;
}
