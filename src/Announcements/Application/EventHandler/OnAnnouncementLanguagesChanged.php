<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Domain\Event\AnnouncementLanguagesChanged;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnAnnouncementLanguagesChanged
{
    public function __invoke(AnnouncementLanguagesChanged $event): void
    {
        // TODO: audit log.
    }
}
