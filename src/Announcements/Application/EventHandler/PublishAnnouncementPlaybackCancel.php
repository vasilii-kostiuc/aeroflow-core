<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Publishes CancelAnnouncementPlayback once an announcement has been cancelled.
 *
 * AnnouncementCancelled is recorded by the aggregate only on the first transition
 * and released only after the local transaction commits, so a repeated cancel of
 * the same announcement never duplicates the integration command. Playback drops
 * the still-pending job; a job that already plays is left alone — stopping the
 * current sound is a separate future command.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class PublishAnnouncementPlaybackCancel
{
    public function __construct(
        private PlaybackRequestPublisherInterface $publisher,
    ) {
    }

    public function __invoke(AnnouncementCancelled $event): void
    {
        $this->publisher->publishCancel(new CancelAnnouncementPlayback(
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $event->announcementId,
            announcementId: $event->announcementId,
            occurredAt: $event->occurredAt->format(DateTimeInterface::ATOM),
        ));
    }
}
