<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Integration command published to aeroflow-playback when an emergency announcement
 * has been created (task 026): suspend the ordinary queue until the mode is lifted.
 *
 * Control-plane, not payload: it carries no audio sequence and no announcement — the
 * emergency announcement itself travels as an ordinary RequestAnnouncementPlayback
 * (priority 1000) on the announcement_playback queue. This command goes over its own
 * emergency_commands transport (ADR 002, level 2) so it never waits behind the bulk
 * queue of ordinary playback requests.
 */
final readonly class ActivateEmergencyMode
{
    public const string COMMAND = 'emergency_mode.activate';

    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public string $messageId,
        public string $correlationId,
        public string $occurredAt,
        public int $schemaVersion = self::SCHEMA_VERSION,
        public string $command = self::COMMAND,
    ) {
    }

    /** @param string $correlationId the announcement that triggered the mode */
    public static function correlatedWith(string $correlationId, DateTimeImmutable $occurredAt): self
    {
        return new self(
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $correlationId,
            occurredAt: $occurredAt->format(DateTimeInterface::ATOM),
        );
    }
}
