<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Repository;

use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightOccurrenceSource;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface FlightOccurrenceRepositoryInterface
{
    public function save(FlightOccurrence $occurrence): void;

    public function findById(Uuid $id): ?FlightOccurrence;

    public function findOneByBusinessKey(
        Uuid $flightDefinitionId,
        DateTimeImmutable $operationalDate,
        FlightOccurrenceSource $source,
        int $sequenceNumber,
    ): ?FlightOccurrence;
}
