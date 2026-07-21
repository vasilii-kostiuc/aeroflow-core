<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends RequestAnnouncementPlayback to aeroflow-playback. The message is routed to
 * the async (RabbitMQ) transport by the Messenger routing configuration; Core has
 * no local handler for it. A durable transactional outbox is a later step and would
 * replace only this infrastructure, not the application port.
 */
final readonly class MessengerPlaybackRequestPublisher implements PlaybackRequestPublisherInterface
{
    public function __construct(
        #[Autowire(service: 'integration.bus')]
        private MessageBusInterface $bus,
    ) {
    }

    public function publish(RequestAnnouncementPlayback $message): void
    {
        $this->bus->dispatch($message);
    }

    public function publishCancel(CancelAnnouncementPlayback $message): void
    {
        $this->bus->dispatch($message);
    }

    public function publishStop(StopAnnouncementPlayback $message): void
    {
        $this->bus->dispatch($message);
    }

    public function publishStopRepeat(StopAnnouncementRepeat $message): void
    {
        $this->bus->dispatch($message);
    }
}
