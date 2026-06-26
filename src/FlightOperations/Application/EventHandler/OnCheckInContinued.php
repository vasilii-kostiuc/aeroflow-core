<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\CheckInContinued;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnCheckInContinued
{
    public function __invoke(CheckInContinued $event): void
    {
        // TODO: audit log.
    }
}
