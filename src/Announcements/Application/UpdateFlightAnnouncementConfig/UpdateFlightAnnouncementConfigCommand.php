<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateFlightAnnouncementConfig;

final readonly class UpdateFlightAnnouncementConfigCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $configId,
        public bool $enabled,
        public ?int $repeatEveryMinutes,
    ) {
    }
}
