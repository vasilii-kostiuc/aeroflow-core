<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\CheckInContinued;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @deprecated task 020 — CheckInContinued is no longer emitted by any flow (the
 *   continuation now repeats a single Announcement in playback). Kept only so a
 *   stray legacy event does not fail with no handler; safe to remove with the event.
 */
#[AsMessageHandler(bus: 'event.bus')]
final class OnCheckInContinued
{
    public function __invoke(CheckInContinued $event): void
    {
        // TODO: audit log.
    }
}
