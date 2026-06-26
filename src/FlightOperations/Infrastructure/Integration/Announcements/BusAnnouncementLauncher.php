<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Integration\Announcements;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\CreateAnnouncement\CreateAnnouncementCommand;
use App\FlightOperations\Application\Port\Announcements\AnnouncementLauncherInterface;
use App\FlightOperations\Application\Port\Announcements\LaunchedAnnouncement;
use App\Shared\Application\Bus\ApplicationBus;

/**
 * Adapter for the Flight Operations -> Announcements port.
 *
 * Dispatches the Announcements create-announcement command synchronously on the
 * shared application bus. Because the orchestrating command handler runs inside a
 * single Doctrine transaction (command.bus doctrine_transaction middleware), the
 * announcement persistence and the occurrence transition commit atomically.
 */
final readonly class BusAnnouncementLauncher implements AnnouncementLauncherInterface
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    public function launch(
        string $flightDefinitionId,
        string $flightOccurrenceId,
        string $announcementType,
        array $languages,
        array $checkInCounterIds,
        ?string $gateId,
    ): LaunchedAnnouncement {
        $result = $this->bus->handleAs(new CreateAnnouncementCommand(
            type: $announcementType,
            languages: $languages,
            flightDefinitionId: $flightDefinitionId,
            flightOccurrenceId: $flightOccurrenceId,
            checkInCounterIds: $checkInCounterIds,
            gateId: $gateId,
        ), AnnouncementResult::class);

        return new LaunchedAnnouncement($result->id, $result->checkInCounters, $result->gate);
    }
}
