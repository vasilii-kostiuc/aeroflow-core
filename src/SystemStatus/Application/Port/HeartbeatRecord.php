<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Port;

use DateTimeImmutable;

/** The last "alive" report of one worker process group. */
final readonly class HeartbeatRecord
{
    /**
     * @param list<string>        $transports the transports this worker consumes
     * @param array<string,mixed> $details    service-specific: pendingJobs, playingSince
     */
    public function __construct(
        public string $service,
        public array $transports,
        public DateTimeImmutable $lastSeenAt,
        public array $details = [],
    ) {
    }
}
