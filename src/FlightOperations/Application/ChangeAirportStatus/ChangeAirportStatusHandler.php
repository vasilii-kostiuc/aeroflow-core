<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\ChangeAirportStatus;

use App\FlightOperations\Application\AirportResult;
use App\FlightOperations\Domain\Exception\AirportNotFoundException;
use App\FlightOperations\Domain\Repository\AirportRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangeAirportStatusHandler
{
    public function __construct(private AirportRepositoryInterface $repository)
    {
    }

    public function __invoke(ChangeAirportStatusCommand $command): AirportResult
    {
        $airport = $this->repository->findById(UuidParser::parse($command->id))
            ?? throw AirportNotFoundException::withId($command->id);
        $changed = $command->active ? $airport->activate() : $airport->deactivate();

        if ($changed) {
            $this->repository->save($airport);
        }

        return AirportResult::fromEntity($airport);
    }
}
