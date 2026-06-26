<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Port\Announcements;

/**
 * Consumer-owned port: Flight Operations asks Announcements to prepare and
 * persist the announcement that belongs to an occurrence transition.
 *
 * The implementation lives in this context's Infrastructure/Integration and
 * adapts the Announcements context. Announcements (the provider) does not depend
 * on this port. The port returns only the announcement id and the resource
 * snapshots the occurrence needs to store — never an Announcements aggregate.
 */
interface AnnouncementLauncherInterface
{
    /**
     * @param list<string> $languages
     * @param list<string> $checkInCounterIds
     *
     * @throws \App\Shared\Domain\DomainException when the announcement cannot be
     *                                            prepared (precondition of the transition)
     */
    public function launch(
        string $flightDefinitionId,
        string $flightOccurrenceId,
        string $announcementType,
        array $languages,
        array $checkInCounterIds,
        ?string $gateId,
    ): LaunchedAnnouncement;
}
