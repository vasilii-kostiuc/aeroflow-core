<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Publishes RequestAnnouncementPlayback once a flight announcement has been created.
 *
 * AnnouncementCreated is released only after the local transaction commits (see
 * DeferredDomainEventPublisher), so the prepared announcement is already persisted
 * and can be loaded to build the neutral playback contract. The announcement owns
 * the audio sequence, which is why this context — not Flight Operations — is the
 * publisher: Flight Operations never learns about playback.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class PublishAnnouncementPlaybackRequest
{
    /**
     * Flight announcements all carry the same playback priority (see priorities in
     * aeroflow-docs); additional and emergency announcements arrive in later slices.
     */
    private const int FLIGHT_ANNOUNCEMENT_PRIORITY = 100;

    public function __construct(
        private AnnouncementRepositoryInterface $announcements,
        private PlaybackRequestPublisherInterface $publisher,
    ) {
    }

    public function __invoke(AnnouncementCreated $event): void
    {
        $announcement = $this->announcements->findById(Uuid::fromString($event->announcementId));
        if ($announcement === null) {
            return;
        }

        $this->publisher->publish(new RequestAnnouncementPlayback(
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $event->announcementId,
            announcementId: $event->announcementId,
            type: $announcement->getType()->value,
            priority: self::FLIGHT_ANNOUNCEMENT_PRIORITY,
            audioSequence: $announcement->getAudioSequence(),
            repeatRule: null,
            occurredAt: $event->occurredAt->format(DateTimeInterface::ATOM),
        ));
    }
}
