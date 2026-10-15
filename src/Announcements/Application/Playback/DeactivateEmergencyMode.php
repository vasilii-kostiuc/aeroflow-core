<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Uid\Uuid;

/**
 * Integration command published to aeroflow-playback when the dispatcher lifts
 * emergency mode (task 026): the ordinary queue may start jobs again.
 *
 * A neutral operator command like StopAnnouncementPlayback — no Announcement is
 * created or changed by lifting the mode, so there is no domain event to react to
 * and the correlation is the operator action itself (messageId). Travels over the
 * dedicated emergency_commands transport (ADR 002, level 2).
 */
final readonly class DeactivateEmergencyMode implements PlaybackCommand
{
    public const string COMMAND = 'emergency_mode.deactivate';

    public const int SCHEMA_VERSION = 1;

    public function __construct(
        public string $messageId,
        public string $correlationId,
        public string $occurredAt,
        public int $schemaVersion = self::SCHEMA_VERSION,
        public string $command = self::COMMAND,
    ) {
    }

    public static function now(): self
    {
        $messageId = Uuid::v7()->toRfc4122();

        return new self(
            messageId: $messageId,
            correlationId: $messageId,
            occurredAt: new DateTimeImmutable('now', new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM),
        );
    }
}
