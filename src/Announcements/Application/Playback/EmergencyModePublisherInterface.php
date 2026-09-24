<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Application-layer publication point for the emergency mode control commands
 * (task 026). Separate from PlaybackRequestPublisherInterface because these travel
 * over their own transport (ADR 002, level 2): an emergency signal must not queue
 * behind the bulk announcement_playback traffic, and keeping the seam explicit makes
 * that routing decision visible in the application layer.
 */
interface EmergencyModePublisherInterface
{
    public function publishActivate(ActivateEmergencyMode $message): void;

    public function publishDeactivate(DeactivateEmergencyMode $message): void;
}
