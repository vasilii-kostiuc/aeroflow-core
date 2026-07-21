<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Integration command published to aeroflow-playback when the repeat series of a
 * continuation announcement ends (task 020), i.e. check-in closed. It is a neutral
 * operator-style command, not a cancellation: the announcement stays Prepared, only
 * playback stops cycling the job. Carries identifiers and time only, like the cancel
 * and stop commands; discriminated in the body by `command` (ADR 002).
 */
final readonly class StopAnnouncementRepeat
{
    public const string COMMAND = 'announcement_playback.stop_repeat';

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
