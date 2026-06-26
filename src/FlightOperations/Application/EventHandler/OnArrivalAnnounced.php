<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\ArrivalAnnounced;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnArrivalAnnounced
{
    public function __invoke(ArrivalAnnounced $event): void
    {
        // TODO: audit log.
    }
}
