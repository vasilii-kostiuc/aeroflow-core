<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum AnnouncementStatus: string
{
    case PendingPreparation = 'pending_preparation';
    case Cancelled = 'cancelled';
}
