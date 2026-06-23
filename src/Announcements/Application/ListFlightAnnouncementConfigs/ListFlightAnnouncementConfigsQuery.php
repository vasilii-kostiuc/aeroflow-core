<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListFlightAnnouncementConfigs;

final readonly class ListFlightAnnouncementConfigsQuery
{
    public function __construct(public string $flightDefinitionId)
    {
    }
}
