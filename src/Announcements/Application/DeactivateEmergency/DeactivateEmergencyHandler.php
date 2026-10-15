<?php

declare(strict_types=1);

namespace App\Announcements\Application\DeactivateEmergency;

use App\Announcements\Application\Playback\DeactivateEmergencyMode;
use App\Announcements\Application\Playback\PlaybackCommandPublisherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Neutral operator action, like StopPlayback (task 019): lifting emergency mode
 * changes no Announcement — the emergency announcement stays the business fact it
 * was — it only tells playback that the ordinary queue may run again. Playback
 * makes the command idempotent, so a repeated click is harmless.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeactivateEmergencyHandler
{
    public function __construct(private PlaybackCommandPublisherInterface $publisher)
    {
    }

    public function __invoke(DeactivateEmergencyCommand $command): void
    {
        $this->publisher->publish(DeactivateEmergencyMode::now());
    }
}
