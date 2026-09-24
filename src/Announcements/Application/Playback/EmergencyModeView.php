<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * The last known state of emergency mode, as reported by playback. `since` is the
 * contract moment of that report (as sent), or null when nothing was reported yet.
 */
final readonly class EmergencyModeView
{
    public function __construct(
        public bool $active,
        public ?string $since,
    ) {
    }
}
