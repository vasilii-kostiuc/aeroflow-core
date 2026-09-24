<?php

declare(strict_types=1);

namespace App\Announcements\Application\ChangeEmergencyTemplateStatus;

final readonly class ChangeEmergencyTemplateStatusCommand
{
    public function __construct(
        public string $id,
        public bool $active,
    ) {
    }
}
