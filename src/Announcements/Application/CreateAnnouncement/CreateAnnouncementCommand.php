<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateAnnouncement;

final readonly class CreateAnnouncementCommand
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        public string $type,
        public string $flightDefinitionId,
        public array $languages,
        public ?int $checkInCounterStart = null,
        public ?int $checkInCounterEnd = null,
        public ?string $gateCode = null,
    ) {
    }
}
