<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Playback\PlaybackCommandPublisherInterface;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Activates emergency mode in playback once an emergency announcement has been
 * created (task 026).
 *
 * The dispatcher performs one action; it has two effects, both published after the
 * local transaction commits from the same AnnouncementCreated fact: the ordinary
 * PublishAnnouncementPlaybackRequest queues the announcement at priority 1000, and
 * this handler tells playback to suspend the ordinary queue until the mode is
 * lifted. Other announcement types are ignored — the type is what makes an
 * announcement an emergency, exactly as it is what gives it its priority.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class PublishEmergencyModeActivation
{
    public function __construct(private PlaybackCommandPublisherInterface $publisher)
    {
    }

    public function __invoke(AnnouncementCreated $event): void
    {
        if ($event->type !== AnnouncementType::Emergency->value) {
            return;
        }

        $this->publisher->publish(
            ActivateEmergencyMode::correlatedWith($event->announcementId, $event->occurredAt),
        );
    }
}
