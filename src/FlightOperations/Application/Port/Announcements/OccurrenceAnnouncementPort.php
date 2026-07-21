<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Port\Announcements;

/**
 * Consumer-owned port: Flight Operations asks Announcements for the announcement
 * operations that co-commit with an occurrence transition (task 020 renamed this
 * from AnnouncementLauncherInterface — the two methods are the same kind of thing,
 * synchronous Announcements mutations inside the occurrence transaction, so the
 * name is a role, not a single action).
 *
 * The implementation lives in this context's Infrastructure/Integration and adapts
 * the Announcements context. Announcements (the provider) does not depend on this
 * port. The port returns only ids and the resource snapshots the occurrence needs —
 * never an Announcements aggregate.
 */
interface OccurrenceAnnouncementPort
{
    /**
     * Prepare and persist the announcement of an occurrence transition.
     *
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

    /**
     * Start the continuation repeat series when check-in opens, if the flight has a
     * configured continuation (task 020). Returns null when none is configured, so
     * opening a flight without a continuation simply proceeds. When a continuation is
     * configured its readiness is a precondition of the open, like the opening
     * announcement: an unresolvable template throws and rolls the transition back.
     *
     * @param list<string> $checkInCounterIds the same counters selected for opening
     */
    public function launchContinuationIfConfigured(
        string $flightDefinitionId,
        string $flightOccurrenceId,
        array $checkInCounterIds,
    ): ?LaunchedAnnouncement;

    /**
     * End the active continuation repeat series of an occurrence when check-in closes.
     * A no-op when none is active (never configured, or already ended).
     */
    public function endContinuationRepeat(string $flightOccurrenceId): void;
}
