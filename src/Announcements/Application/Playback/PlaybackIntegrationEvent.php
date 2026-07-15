<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Inbound integration event from aeroflow-playback (queued / started / completed).
 *
 * Core owns its own copy of the contract: the message is rebuilt from the JSON body
 * by a transport serializer, discriminated by the `event` field — never by
 * playback's PHP class names. This slice treats all playback facts uniformly (the
 * reception is recorded, Announcement statuses do not change), so one message class
 * carries them all; it can be split per event once core reacts differently.
 */
final readonly class PlaybackIntegrationEvent
{
    public function __construct(
        public string $event,
        public string $messageId,
        public string $correlationId,
        public string $announcementId,
        public string $jobId,
        public string $occurredAt,
        public int $schemaVersion,
        public ?string $reason = null,
    ) {
    }
}
