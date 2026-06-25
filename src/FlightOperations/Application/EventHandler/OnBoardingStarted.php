<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\EventHandler;

use App\FlightOperations\Domain\Event\BoardingStarted;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class OnBoardingStarted
{
    public function __invoke(BoardingStarted $event): void
    {
        // TODO: audit log.
    }
}
