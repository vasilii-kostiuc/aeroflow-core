<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Core's eventual copy of playback's emergency mode state (task 026).
 *
 * Both sides of one integration artifact, like the playback receipts: `apply()`
 * records an inbound report, `current()` feeds the dispatcher's banner. It is not a
 * domain aggregate — playback owns the mode; core only mirrors it.
 */
interface EmergencyModeStateInterface
{
    /**
     * Apply an inbound report. Implementations must ignore a redelivered or
     * out-of-order report so a late `activated` cannot resurrect a lifted mode.
     */
    public function apply(EmergencyModeIntegrationEvent $event): void;

    public function current(): EmergencyModeView;
}
