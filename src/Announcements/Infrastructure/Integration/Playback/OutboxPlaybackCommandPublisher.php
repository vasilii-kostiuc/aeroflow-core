<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\DeactivateEmergencyMode;
use App\Announcements\Application\Playback\PlaybackCommand;
use App\Announcements\Application\Playback\PlaybackCommandPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use App\Shared\Infrastructure\Outbox\Outbox;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Writes the commands for aeroflow-playback into the outbox (task 035), inside the
 * transaction of the use case that produced them; the outbox relay delivers them to
 * RabbitMQ after the commit. Encoded with the same serializer the Messenger transport
 * used, so the wire format is unchanged.
 *
 * The queue is chosen by the command's type (task 036): the emergency mode control
 * commands travel on their own queue (ADR 002, level 2) so they never wait behind
 * the bulk of ordinary announcement_playback traffic.
 */
final readonly class OutboxPlaybackCommandPublisher implements PlaybackCommandPublisherInterface
{
    /** @var array<class-string<PlaybackCommand>, string> command => queue */
    private const array DESTINATIONS = [
        RequestAnnouncementPlayback::class => 'announcement_playback',
        CancelAnnouncementPlayback::class => 'announcement_playback',
        StopAnnouncementPlayback::class => 'announcement_playback',
        StopAnnouncementRepeat::class => 'announcement_playback',
        ActivateEmergencyMode::class => 'emergency_commands',
        DeactivateEmergencyMode::class => 'emergency_commands',
    ];

    public function __construct(
        private Outbox $outbox,
        #[Autowire(service: 'messenger.transport.symfony_serializer')]
        private SerializerInterface $serializer,
    ) {
    }

    public function publish(PlaybackCommand $command): void
    {
        $destination = self::DESTINATIONS[$command::class]
            ?? throw new LogicException(sprintf('No playback queue is configured for %s.', $command::class));

        $this->outbox->append($destination, $this->serializer->encode(new Envelope($command)));
    }
}
