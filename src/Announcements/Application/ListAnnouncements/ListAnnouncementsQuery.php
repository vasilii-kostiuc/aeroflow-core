<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListAnnouncements;

final readonly class ListAnnouncementsQuery
{
    public function __construct(public int $limit = 100)
    {
    }
}
