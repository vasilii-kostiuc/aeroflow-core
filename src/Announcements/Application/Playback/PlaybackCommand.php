<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * An integration command the Announcements context sends to aeroflow-playback. The
 * contract DTOs (request, cancel, stop, stop_repeat, emergency mode activate and
 * deactivate) implement it so they share one publication port.
 */
interface PlaybackCommand
{
}
