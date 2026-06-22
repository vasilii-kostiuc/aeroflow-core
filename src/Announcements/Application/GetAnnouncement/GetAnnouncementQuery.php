<?php

declare(strict_types=1);

namespace App\Announcements\Application\GetAnnouncement;

final readonly class GetAnnouncementQuery
{
    public function __construct(public string $id)
    {
    }
}
