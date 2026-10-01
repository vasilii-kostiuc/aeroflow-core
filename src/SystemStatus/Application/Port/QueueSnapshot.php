<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Port;

final readonly class QueueSnapshot
{
    public function __construct(
        public string $name,
        /** Subscribed consumers. Symfony workers poll, so only the agent shows up here. */
        public int $consumers,
        public int $messagesReady,
    ) {
    }
}
