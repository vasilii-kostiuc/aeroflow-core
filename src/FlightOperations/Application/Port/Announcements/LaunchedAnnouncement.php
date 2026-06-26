<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Port\Announcements;

final readonly class LaunchedAnnouncement
{
    /**
     * @param list<array{id:string,code:string}> $checkInCounters
     * @param array{id:string,code:string}|null  $gate
     */
    public function __construct(
        public string $announcementId,
        public array $checkInCounters,
        public ?array $gate,
    ) {
    }
}
