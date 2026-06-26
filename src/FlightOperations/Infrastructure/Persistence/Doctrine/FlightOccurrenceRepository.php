<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightOccurrenceSource;
use App\FlightOperations\Domain\Exception\DuplicateFlightOccurrenceException;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FlightOccurrenceRepository implements FlightOccurrenceRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(FlightOccurrence $occurrence): void
    {
        try {
            $this->entityManager->persist($occurrence);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateFlightOccurrenceException::forBusinessKey();
        }
    }

    public function findById(Uuid $id): ?FlightOccurrence
    {
        return $this->entityManager->find(FlightOccurrence::class, $id);
    }

    public function findOneByBusinessKey(
        Uuid $flightDefinitionId,
        DateTimeImmutable $operationalDate,
        FlightOccurrenceSource $source,
        int $sequenceNumber,
    ): ?FlightOccurrence {
        return $this->entityManager->getRepository(FlightOccurrence::class)->findOneBy([
            'flightDefinitionId' => $flightDefinitionId,
            'operationalDate' => new DateTimeImmutable($operationalDate->format('Y-m-d')),
            'source' => $source,
            'sequenceNumber' => $sequenceNumber,
        ]);
    }
}
