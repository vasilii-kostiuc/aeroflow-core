<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListSupplementaryTemplates;

final readonly class ListSupplementaryTemplatesQuery
{
    public function __construct(public ?bool $active = null)
    {
    }
}
