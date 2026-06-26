<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListDispatcherFlightOccurrencesHandler
{
    public function __construct(private DispatcherFlightOccurrenceQueryInterface $query)
    {
    }

    /** @return list<DispatcherFlightOccurrenceResult> */
    public function __invoke(ListDispatcherFlightOccurrencesQuery $query): array
    {
        return $this->query->search(
            $query->operationalDate,
            $query->announcementType,
            $query->direction,
            $query->includeUnavailable,
        );
    }
}
