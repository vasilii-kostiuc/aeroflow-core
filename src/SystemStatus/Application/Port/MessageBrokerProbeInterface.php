<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Port;

interface MessageBrokerProbeInterface
{
    /**
     * @return list<QueueSnapshot>|null null when the broker cannot be reached
     */
    public function queues(): ?array;
}
