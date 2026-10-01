<?php

declare(strict_types=1);

namespace App\SystemStatus\Application;

/** Something the dispatcher must know: announcements may not sound right now. */
final readonly class SystemAlert
{
    public function __construct(
        public string $key,
        public string $message,
    ) {
    }
}
