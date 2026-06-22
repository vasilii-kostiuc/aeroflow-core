<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Domain\Event\AnnouncementCancelled;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnAnnouncementCancelled
{
    public function __invoke(AnnouncementCancelled $event): void
    {
        // TODO: audit log.
    }
}
