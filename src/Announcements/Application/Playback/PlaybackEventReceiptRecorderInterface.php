<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Records the fact that a playback integration event was received.
 *
 * Minimal reception of the second playback slice: the fact is persisted for
 * observability and audit, Announcement statuses are not touched (extending the
 * Announcement lifecycle from playback facts is a separate task).
 */
interface PlaybackEventReceiptRecorderInterface
{
    /**
     * Record the event once. Returns false when this messageId was already
     * recorded, so a redelivery leaves no duplicate.
     */
    public function recordOnce(PlaybackIntegrationEvent $event): bool;
}
