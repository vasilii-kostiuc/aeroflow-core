<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateFlightAnnouncementConfig;

final readonly class CreateFlightAnnouncementConfigCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $announcementType,
        public bool $enabled,
        public ?int $repeatEveryMinutes,
    ) {
    }
}
