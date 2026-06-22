<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\GetAirport;

use App\FlightOperations\Application\AirportResult;
use App\FlightOperations\Domain\Exception\AirportNotFoundException;
use App\FlightOperations\Domain\Repository\AirportRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetAirportHandler
{
    public function __construct(private AirportRepositoryInterface $repository)
    {
    }

    public function __invoke(GetAirportQuery $query): AirportResult
    {
        return AirportResult::fromEntity(
            $this->repository->findById(UuidParser::parse($query->id))
                ?? throw AirportNotFoundException::withId($query->id),
        );
    }
}
