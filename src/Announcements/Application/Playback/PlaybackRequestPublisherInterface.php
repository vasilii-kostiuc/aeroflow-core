<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Application-layer publication point for the outbound playback integration
 * commands. Keeping it behind a port lets the transport (and, later, a durable
 * outbox) change without touching the domain model. The infrastructure
 * implementation routes the commands to aeroflow-playback over RabbitMQ.
 */
interface PlaybackRequestPublisherInterface
{
    public function publish(RequestAnnouncementPlayback $message): void;

    public function publishCancel(CancelAnnouncementPlayback $message): void;

    public function publishStop(StopAnnouncementPlayback $message): void;

    public function publishStopRepeat(StopAnnouncementRepeat $message): void;
}
