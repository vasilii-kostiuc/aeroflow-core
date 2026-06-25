<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Event\DomainEventPublisher;

final class RecordingEventPublisher implements DomainEventPublisher
{
    /**
     * @var list<object>
     */
    public array $messages = [];

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->messages[] = $event;
        }
    }
}
