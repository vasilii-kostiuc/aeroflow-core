<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Domain\Entity\Gate;
use App\FlightOperations\Domain\Exception\DuplicateOperationalResourceException;
use App\FlightOperations\Domain\Repository\GateRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class GateRepository implements GateRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Gate $gate): void
    {
        try {
            $this->entityManager->persist($gate);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateOperationalResourceException::forTypeAndCode('Gate', $gate->getCode()->toString());
        }
    }

    public function findById(Uuid $id): ?Gate
    {
        return $this->entityManager->find(Gate::class, $id);
    }

    public function findByCode(OperationalResourceCode $code): ?Gate
    {
        return $this->entityManager->getRepository(Gate::class)->findOneBy(['code.value' => $code->toString()]);
    }

    public function findAll(?bool $active = null): array
    {
        $criteria = $active === null ? [] : ['active' => $active];

        return $this->entityManager->getRepository(Gate::class)->findBy($criteria, ['sortOrder' => 'ASC', 'code.value' => 'ASC']);
    }
}
