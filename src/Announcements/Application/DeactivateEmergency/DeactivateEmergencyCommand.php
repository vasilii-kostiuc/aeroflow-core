<?php

declare(strict_types=1);

namespace App\Announcements\Application\DeactivateEmergency;

/**
 * The dispatcher lifts emergency mode. Carries nothing: the mode is global and
 * singular, and no Announcement is involved.
 */
final readonly class DeactivateEmergencyCommand
{
}
