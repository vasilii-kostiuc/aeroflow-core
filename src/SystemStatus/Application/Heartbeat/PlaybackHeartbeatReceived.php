<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Heartbeat;

/**
 * Inbound `playback.heartbeat` from aeroflow-playback (task 034): a playback worker
 * is alive and serves these transports. Global, like the emergency mode reports:
 * no announcement, no job.
 */
final readonly class PlaybackHeartbeatReceived
{
    public const string EVENT = 'playback.heartbeat';

    /**
     * @param list<string> $transports
     */
    public function __construct(
        public string $messageId,
        public string $occurredAt,
        public int $schemaVersion,
        public array $transports,
        public int $pendingJobs,
        public ?string $playingSince,
    ) {
    }
}
