<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use App\Announcements\Domain\Event\AnnouncementRepeatEnded;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Publishes StopAnnouncementRepeat once a continuation series has ended (task 020).
 *
 * AnnouncementRepeatEnded is recorded by the aggregate only on the first transition
 * and released only after the local transaction commits, so a repeated end of the
 * same series never duplicates the integration command. Mirrors the request/cancel
 * publishers: the Announcements context owns the playback contract, Flight Operations
 * never learns about playback.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class PublishAnnouncementPlaybackStopRepeat
{
    public function __construct(
        private PlaybackRequestPublisherInterface $publisher,
    ) {
    }

    public function __invoke(AnnouncementRepeatEnded $event): void
    {
        $this->publisher->publishStopRepeat(new StopAnnouncementRepeat(
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $event->announcementId,
            announcementId: $event->announcementId,
            occurredAt: $event->occurredAt->format(DateTimeInterface::ATOM),
        ));
    }
}
