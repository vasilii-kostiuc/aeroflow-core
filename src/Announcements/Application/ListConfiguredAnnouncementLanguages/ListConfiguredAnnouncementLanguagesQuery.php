<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListConfiguredAnnouncementLanguages;

/**
 * Read query: which languages are configured (and enabled) for a flight's
 * announcement of a given type. Used by the dispatcher panel to pre-select the
 * languages the announcement would actually play in.
 */
final readonly class ListConfiguredAnnouncementLanguagesQuery
{
    public function __construct(
        public string $flightDefinitionId,
        public string $announcementType,
    ) {
    }
}
