<?php

declare(strict_types=1);

namespace App\Announcements\Application\EndOccurrenceContinuationRepeat;

/**
 * Ends the active continuation repeat series of an occurrence (task 020), invoked by
 * Flight Operations through its port when check-in closes. A no-op when the occurrence
 * has no active continuation series (none was configured, or it already ended).
 */
final readonly class EndOccurrenceContinuationRepeatCommand
{
    public function __construct(public string $flightOccurrenceId)
    {
    }
}
