<?php

declare(strict_types=1);

namespace App\Announcements\Application\EventHandler;

use App\Announcements\Domain\Event\AnnouncementTemplateSegmentAdded;
use App\Announcements\Domain\Event\AnnouncementTemplateSegmentRemoved;
use App\Announcements\Domain\Event\AnnouncementTemplateSegmentUpdated;
use App\Announcements\Domain\Event\AnnouncementVariantAdded;
use App\Announcements\Domain\Event\AnnouncementVariantDisabled;
use App\Announcements\Domain\Event\AnnouncementVariantEnabled;
use App\Announcements\Domain\Event\AnnouncementVariantRemoved;
use App\Announcements\Domain\Event\AnnouncementVariantUpdated;
use App\Announcements\Domain\Event\FlightAnnouncementConfigCreated;
use App\Announcements\Domain\Event\FlightAnnouncementConfigDisabled;
use App\Announcements\Domain\Event\FlightAnnouncementConfigEnabled;
use App\Announcements\Domain\Event\FlightAnnouncementConfigUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class OnFlightAnnouncementConfigChanged
{
    #[AsMessageHandler(bus: 'event.bus')]
    public function created(FlightAnnouncementConfigCreated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function updated(FlightAnnouncementConfigUpdated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function enabled(FlightAnnouncementConfigEnabled $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function disabled(FlightAnnouncementConfigDisabled $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function variantAdded(AnnouncementVariantAdded $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function variantUpdated(AnnouncementVariantUpdated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function variantEnabled(AnnouncementVariantEnabled $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function variantDisabled(AnnouncementVariantDisabled $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function variantRemoved(AnnouncementVariantRemoved $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function segmentAdded(AnnouncementTemplateSegmentAdded $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function segmentUpdated(AnnouncementTemplateSegmentUpdated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function segmentRemoved(AnnouncementTemplateSegmentRemoved $event): void
    {
    }
}
