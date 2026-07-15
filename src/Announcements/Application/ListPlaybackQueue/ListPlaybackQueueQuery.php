<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListPlaybackQueue;

/**
 * Dispatcher's queue screen (task 017, heir of the legacy Status window):
 * what is playing now, what waits, what just finished.
 */
final readonly class ListPlaybackQueueQuery
{
    public function __construct(public int $recentLimit = 10)
    {
    }
}
