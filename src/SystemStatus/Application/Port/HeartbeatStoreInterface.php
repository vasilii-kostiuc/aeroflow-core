<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Port;

/**
 * Last heartbeat per worker (one record per service + set of transports), core's
 * own worker and playback's alike (task 034). Newest wins; an older, redelivered
 * report never overwrites a newer one.
 */
interface HeartbeatStoreInterface
{
    public function record(HeartbeatRecord $heartbeat): void;

    /** @return list<HeartbeatRecord> */
    public function all(): array;
}
