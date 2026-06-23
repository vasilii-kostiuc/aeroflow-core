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
        public string $flightDefinitionId,
        public array $languages,
        public array $checkInCounterIds = [],
        public ?string $gateId = null,
    ) {
    }
}
