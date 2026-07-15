<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListPlaybackQueue;

final readonly class PlaybackQueueResult
{
    /**
     * @param list<PlaybackQueueRowResult> $waiting
     * @param list<PlaybackQueueRowResult> $recent
     */
    public function __construct(
        public ?PlaybackQueueRowResult $playing,
        public array $waiting,
        public array $recent,
    ) {
    }
}
