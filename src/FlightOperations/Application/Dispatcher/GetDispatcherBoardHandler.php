<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\Dispatcher;

use App\FlightOperations\Application\OperationalDay\OperationalDayProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * The board shows each card's current run for today's operational day. A run
 * started the day before and not yet finished stays on it past midnight, so the
 * dispatcher can still close check-in and board the flight (task 027).
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetDispatcherBoardHandler
{
    public function __construct(
        private DispatcherFlightOccurrenceQueryInterface $query,
        private OperationalDayProviderInterface $operationalDay,
    ) {
    }

    public function __invoke(GetDispatcherBoardQuery $query): DispatcherBoardResult
    {
        $operationalDate = $this->operationalDay->today();

        return new DispatcherBoardResult(
            $operationalDate->format('Y-m-d'),
            $this->query->board($operationalDate),
        );
    }
}
