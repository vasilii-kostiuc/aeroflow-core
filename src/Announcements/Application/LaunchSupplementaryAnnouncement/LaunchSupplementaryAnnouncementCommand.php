<?php

declare(strict_types=1);

namespace App\Announcements\Application\LaunchSupplementaryAnnouncement;

final readonly class LaunchSupplementaryAnnouncementCommand
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
