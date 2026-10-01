<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Heartbeat;

use App\SystemStatus\Application\Port\HeartbeatRecord;
use App\SystemStatus\Application\Port\HeartbeatStoreInterface;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Keeps the last heartbeat of each playback worker. Redelivery is harmless: newest wins. */
#[AsMessageHandler]
final readonly class RecordPlaybackHeartbeat
{
    public function __construct(private HeartbeatStoreInterface $store)
    {
    }

    public function __invoke(PlaybackHeartbeatReceived $heartbeat): void
    {
        try {
            $seenAt = new DateTimeImmutable($heartbeat->occurredAt);
        } catch (Exception) {
            return;
        }

        $this->store->record(new HeartbeatRecord(
            'playback',
            $heartbeat->transports,
            $seenAt,
            ['pendingJobs' => $heartbeat->pendingJobs, 'playingSince' => $heartbeat->playingSince],
        ));
    }
}
