<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Uid\Uuid;

/** Neutral operator command: stop the Playback job, without mutating Announcement. */
final readonly class StopAnnouncementPlayback
{
    /** Body-level discriminator (ADR 002), mirrors RequestAnnouncementPlayback. */
    public const string COMMAND = 'announcement_playback.stop';

    public const int SCHEMA_VERSION = 1;

    public static function forAnnouncement(string $announcementId): self
    {
        return new self(
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $announcementId,
            announcementId: $announcementId,
            occurredAt: (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        );
    }

    private function __construct(
        public string $messageId,
        public string $correlationId,
        public string $announcementId,
        public string $occurredAt,
        public int $schemaVersion = self::SCHEMA_VERSION,
        public string $command = self::COMMAND,
    ) {
    }
}
