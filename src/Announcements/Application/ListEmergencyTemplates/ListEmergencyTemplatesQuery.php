<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListEmergencyTemplates;

final readonly class ListEmergencyTemplatesQuery
{
    public function __construct(public ?bool $active = null)
    {
    }
}
