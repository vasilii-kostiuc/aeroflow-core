<?php

declare(strict_types=1);

namespace App\Announcements\Application\StopPlayback;

final readonly class StopPlaybackCommand
{
    public function __construct(public string $announcementId)
    {
    }
}
