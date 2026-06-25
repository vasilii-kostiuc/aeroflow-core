<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateAnnouncement;

final readonly class CreateAnnouncementCommand
{
    /**
     * @param list<string> $languages
     * @param list<string> $checkInCounterIds
     */
    public function __construct(
        public string $type,
        public array $languages,
        public ?string $flightDefinitionId = null,
        public ?string $flightOccurrenceId = null,
        public array $checkInCounterIds = [],
        public ?string $gateId = null,
    ) {
    }
}
