<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use App\Shared\Infrastructure\Outbox\Outbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Writes the announcement_playback commands for aeroflow-playback into the outbox
 * (task 035), inside the transaction of the use case that produced them; the
 * outbox relay delivers them to RabbitMQ after the commit. Encoded with the same
 * serializer the Messenger transport used, so the wire format is unchanged.
 */
final readonly class OutboxPlaybackRequestPublisher implements PlaybackRequestPublisherInterface
{
    public const string DESTINATION = 'announcement_playback';

    public function __construct(
        private Outbox $outbox,
        #[Autowire(service: 'messenger.transport.symfony_serializer')]
        private SerializerInterface $serializer,
    ) {
    }

    public function publish(RequestAnnouncementPlayback $message): void
    {
        $this->append($message);
    }

    public function publishCancel(CancelAnnouncementPlayback $message): void
    {
        $this->append($message);
    }

    public function publishStop(StopAnnouncementPlayback $message): void
    {
        $this->append($message);
    }

    public function publishStopRepeat(StopAnnouncementRepeat $message): void
    {
        $this->append($message);
    }

    private function append(object $message): void
    {
        $this->outbox->append(self::DESTINATION, $this->serializer->encode(new Envelope($message)));
    }
}
