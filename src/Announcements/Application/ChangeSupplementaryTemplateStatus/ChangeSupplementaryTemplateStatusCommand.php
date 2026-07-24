<?php

declare(strict_types=1);

namespace App\Announcements\Application\ChangeSupplementaryTemplateStatus;

final readonly class ChangeSupplementaryTemplateStatusCommand
{
    public function __construct(
        public string $id,
        public bool $active,
    ) {
    }
}
