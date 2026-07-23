<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use DateTimeImmutable;

/**
 * One recorded playback fact, as the queue read model consumes it.
 */
final readonly class PlaybackEventReceiptView
{
    public function __construct(
        public string $event,
        public string $announcementId,
        public string $jobId,
        public DateTimeImmutable $receivedAt,
        public ?string $reason = null,
        public ?string $nextAt = null,
    ) {
    }
}
