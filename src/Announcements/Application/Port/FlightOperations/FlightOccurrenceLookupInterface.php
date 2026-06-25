<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\FlightOperations;

interface FlightOccurrenceLookupInterface
{
    public function findById(string $id): ?FlightOccurrenceSnapshot;

    /**
     * @param list<array{id:string,code:string}> $checkInCounters
     * @param array{id:string,code:string}|null  $gate
     */
    public function assertCanLaunch(string $id, string $announcementType): void;

    /**
     * @param list<array{id:string,code:string}> $checkInCounters
     * @param array{id:string,code:string}|null  $gate
     */
    public function recordAnnouncementLaunch(
        string $id,
        string $announcementType,
        string $announcementId,
        array $checkInCounters,
        ?array $gate,
    ): void;
}
