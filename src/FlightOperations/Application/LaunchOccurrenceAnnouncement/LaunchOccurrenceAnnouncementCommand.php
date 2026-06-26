<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\LaunchOccurrenceAnnouncement;

final readonly class LaunchOccurrenceAnnouncementCommand
{
    /**
     * @param list<string> $languages
     * @param list<string> $checkInCounterIds
     */
    public function __construct(
        public string $flightOccurrenceId,
        public string $announcementType,
        public array $languages,
        public array $checkInCounterIds = [],
        public ?string $gateId = null,
    ) {
    }
}
