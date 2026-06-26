<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\GetFlightOccurrence;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Domain\Exception\FlightOccurrenceNotFoundException;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetFlightOccurrenceHandler
{
    public function __construct(private FlightOccurrenceRepositoryInterface $occurrences)
    {
    }

    public function __invoke(GetFlightOccurrenceQuery $query): FlightOccurrenceResult
    {
        $id = UuidParser::parse($query->id);
        $occurrence = $this->occurrences->findById($id)
            ?? throw FlightOccurrenceNotFoundException::withId($query->id);

        return FlightOccurrenceResult::fromEntity($occurrence);
    }
}
