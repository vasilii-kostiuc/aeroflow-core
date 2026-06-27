<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Application-layer publication point for the outbound RequestAnnouncementPlayback
 * integration command. Keeping it behind a port lets the transport (and, later, a
 * durable outbox) change without touching the domain model. The infrastructure
 * implementation routes the command to aeroflow-playback over RabbitMQ.
 */
interface PlaybackRequestPublisherInterface
{
    public function publish(RequestAnnouncementPlayback $message): void;
}
