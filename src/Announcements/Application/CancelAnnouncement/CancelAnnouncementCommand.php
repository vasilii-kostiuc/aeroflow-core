<?php

declare(strict_types=1);

namespace App\Announcements\Application\CancelAnnouncement;

final readonly class CancelAnnouncementCommand
{
    public function __construct(public string $id)
    {
    }
}
