<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\CreateAirport;

use App\FlightOperations\Application\AirportResult;
use App\FlightOperations\Domain\Entity\Airport;
use App\FlightOperations\Domain\Exception\DuplicateAirportException;
use App\FlightOperations\Domain\Repository\AirportRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAirportHandler
{
    public function __construct(private AirportRepositoryInterface $repository)
    {
    }

    public function __invoke(CreateAirportCommand $command): AirportResult
    {
        $code = AirportCode::fromString($command->code);

        if ($this->repository->findByCode($code) !== null) {
            throw DuplicateAirportException::withCode($code->toString());
        }

        $airport = Airport::create($code, $command->name, $command->cityName, $command->countryCode);
        $this->repository->save($airport);

        return AirportResult::fromEntity($airport);
    }
}
