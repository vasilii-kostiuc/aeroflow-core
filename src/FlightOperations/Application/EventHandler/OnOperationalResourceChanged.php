<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\CheckInCounterActivated;
use App\FlightOperations\Domain\Event\CheckInCounterCreated;
use App\FlightOperations\Domain\Event\CheckInCounterDeactivated;
use App\FlightOperations\Domain\Event\CheckInCounterUpdated;
use App\FlightOperations\Domain\Event\GateActivated;
use App\FlightOperations\Domain\Event\GateCreated;
use App\FlightOperations\Domain\Event\GateDeactivated;
use App\FlightOperations\Domain\Event\GateUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class OnOperationalResourceChanged
{
    #[AsMessageHandler(bus: 'event.bus')]
    public function counterCreated(CheckInCounterCreated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function counterUpdated(CheckInCounterUpdated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function counterActivated(CheckInCounterActivated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function counterDeactivated(CheckInCounterDeactivated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function gateCreated(GateCreated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function gateUpdated(GateUpdated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function gateActivated(GateActivated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function gateDeactivated(GateDeactivated $event): void
    {
    }
}
