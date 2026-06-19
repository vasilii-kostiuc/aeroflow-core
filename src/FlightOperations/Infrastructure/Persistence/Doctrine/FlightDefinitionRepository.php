<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Exception\DuplicateFlightDefinitionException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FlightDefinitionRepository implements FlightDefinitionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(FlightDefinition $flightDefinition): void
    {
        try {
            $this->entityManager->persist($flightDefinition);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateFlightDefinitionException::create();
        }
    }

    public function findById(Uuid $id): ?FlightDefinition
    {
        return $this->entityManager->find(FlightDefinition::class, $id);
    }

    public function hasConflictingDefinition(
        FlightNumber $flightNumber,
        FlightDirection $direction,
        AirportCode $originAirportCode,
        AirportCode $destinationAirportCode,
        ?Uuid $excludeId = null,
    ): bool {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from(FlightDefinition::class, 'f')
            ->where('f.flightNumber.value = :flightNumber')
            ->andWhere('f.direction = :direction')
            ->andWhere('f.originAirportCode.value = :originAirportCode')
            ->andWhere('f.destinationAirportCode.value = :destinationAirportCode')
            ->setParameter('flightNumber', $flightNumber->toString())
            ->setParameter('direction', $direction->value)
            ->setParameter('originAirportCode', $originAirportCode->toString())
            ->setParameter('destinationAirportCode', $destinationAirportCode->toString());

        if ($excludeId !== null) {
            $queryBuilder
                ->andWhere('f.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
