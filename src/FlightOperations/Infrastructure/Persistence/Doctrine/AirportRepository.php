<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Domain\Entity\Airport;
use App\FlightOperations\Domain\Exception\DuplicateAirportException;
use App\FlightOperations\Domain\Repository\AirportRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AirportRepository implements AirportRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Airport $airport): void
    {
        try {
            $this->entityManager->persist($airport);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateAirportException::withCode($airport->getCode()->toString());
        }
    }

    public function findById(Uuid $id): ?Airport
    {
        return $this->entityManager->find(Airport::class, $id);
    }

    public function findByCode(AirportCode $code): ?Airport
    {
        return $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Airport::class, 'a')
            ->where('a.code.value = :code')
            ->setParameter('code', $code->toString())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
