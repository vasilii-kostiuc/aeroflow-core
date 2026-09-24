<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Inbound integration event from aeroflow-playback reporting the state of emergency
 * mode (task 026): `emergency_mode.activated` / `emergency_mode.deactivated`.
 *
 * Separate from PlaybackIntegrationEvent because it is not about a job: it carries
 * neither announcementId nor jobId — the mode is global. Playback owns the state;
 * core keeps this eventual copy so the dispatcher's banner shows what actually took
 * effect rather than what was merely requested.
 */
final readonly class EmergencyModeIntegrationEvent
{
    public const string EVENT_ACTIVATED = 'emergency_mode.activated';

    public const string EVENT_DEACTIVATED = 'emergency_mode.deactivated';

    public function __construct(
        public string $event,
        public string $messageId,
        public string $occurredAt,
        public int $schemaVersion,
    ) {
    }

    public function isActive(): bool
    {
        return self::EVENT_ACTIVATED === $this->event;
    }
}
