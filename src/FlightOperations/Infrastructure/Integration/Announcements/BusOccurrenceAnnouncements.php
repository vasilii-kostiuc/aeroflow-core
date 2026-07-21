<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Integration\Announcements;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\ConfiguredAnnouncementLanguagesResult;
use App\Announcements\Application\CreateAnnouncement\CreateAnnouncementCommand;
use App\Announcements\Application\EndOccurrenceContinuationRepeat\EndOccurrenceContinuationRepeatCommand;
use App\Announcements\Application\ListConfiguredAnnouncementLanguages\ListConfiguredAnnouncementLanguagesQuery;
use App\FlightOperations\Application\Port\Announcements\LaunchedAnnouncement;
use App\FlightOperations\Application\Port\Announcements\OccurrenceAnnouncementPort;
use App\Shared\Application\Bus\ApplicationBus;

/**
 * Adapter for the Flight Operations -> Announcements port.
 *
 * Dispatches Announcements commands synchronously on the shared application bus.
 * Because the orchestrating command handler runs inside a single Doctrine
 * transaction (command.bus doctrine_transaction middleware), the announcement
 * writes and the occurrence transition commit atomically.
 */
final readonly class BusOccurrenceAnnouncements implements OccurrenceAnnouncementPort
{
    private const string CONTINUATION_TYPE = 'check_in_continuation';

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

    public function launchContinuationIfConfigured(
        string $flightDefinitionId,
        string $flightOccurrenceId,
        array $checkInCounterIds,
    ): ?LaunchedAnnouncement {
        // The continuation uses its own configured, enabled languages. An empty list
        // means the flight has no enabled continuation config, so there is nothing to
        // repeat and opening simply proceeds without a continuation.
        $languages = $this->bus->handleAs(new ListConfiguredAnnouncementLanguagesQuery(
            $flightDefinitionId,
            self::CONTINUATION_TYPE,
        ), ConfiguredAnnouncementLanguagesResult::class)->languages;

        if ($languages === []) {
            return null;
        }

        return $this->launch(
            $flightDefinitionId,
            $flightOccurrenceId,
            self::CONTINUATION_TYPE,
            $languages,
            $checkInCounterIds,
            null,
        );
    }

    public function endContinuationRepeat(string $flightOccurrenceId): void
    {
        $this->bus->dispatch(new EndOccurrenceContinuationRepeatCommand($flightOccurrenceId));
    }
}
