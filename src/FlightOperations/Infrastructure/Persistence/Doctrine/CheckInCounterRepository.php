<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Exception\DuplicateOperationalResourceException;
use App\FlightOperations\Domain\Repository\CheckInCounterRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CheckInCounterRepository implements CheckInCounterRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(CheckInCounter $counter): void
    {
        try {
            $this->entityManager->persist($counter);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateOperationalResourceException::forTypeAndCode('Check-in counter', $counter->getCode()->toString());
        }
    }

    public function findById(Uuid $id): ?CheckInCounter
    {
        return $this->entityManager->find(CheckInCounter::class, $id);
    }

    public function findByCode(OperationalResourceCode $code): ?CheckInCounter
    {
        return $this->entityManager->getRepository(CheckInCounter::class)->findOneBy(['code.value' => $code->toString()]);
    }

    public function findAll(?bool $active = null): array
    {
        $criteria = $active === null ? [] : ['active' => $active];

        return $this->entityManager->getRepository(CheckInCounter::class)->findBy($criteria, ['sortOrder' => 'ASC', 'code.value' => 'ASC']);
    }
}
