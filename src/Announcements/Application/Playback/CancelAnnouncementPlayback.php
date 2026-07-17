<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Integration command published by the Announcements context to aeroflow-playback
 * after an announcement has been cancelled and its local transaction committed.
 *
 * It asks playback to drop the still-pending job of the announcement. Playback
 * decides on its own what that means for a job that already plays or finished —
 * cancelling only removes a waiting job; stopping the current sound is a separate
 * future command. The contract is neutral: identifiers and time only.
 */
final readonly class CancelAnnouncementPlayback
{
    /** Body-level discriminator (ADR 002), mirrors RequestAnnouncementPlayback. */
    public const string COMMAND = 'announcement_playback.cancel';

    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public string $messageId,
        public string $correlationId,
        public string $announcementId,
        public string $occurredAt,
        public int $schemaVersion = self::SCHEMA_VERSION,
        public string $command = self::COMMAND,
    ) {
    }
}
