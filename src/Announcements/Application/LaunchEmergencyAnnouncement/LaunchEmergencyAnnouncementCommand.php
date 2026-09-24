<?php

declare(strict_types=1);

namespace App\Announcements\Application\LaunchEmergencyAnnouncement;

final readonly class LaunchEmergencyAnnouncementCommand
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        public string $templateId,
        public array $languages,
    ) {
    }
}
