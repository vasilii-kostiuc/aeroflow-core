<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Domain\Event\AnnouncementCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnAnnouncementCreated
{
    public function __invoke(AnnouncementCreated $event): void
    {
        // TODO: audit log.
    }
}
