<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\DuplicateFlightAnnouncementConfigException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FlightAnnouncementConfigRepository implements FlightAnnouncementConfigRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(FlightAnnouncementConfig $config): void
    {
        try {
            $this->entityManager->persist($config);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateFlightAnnouncementConfigException::forFlightAndType($config->getFlightDefinitionId()->toRfc4122(), $config->getAnnouncementType()->value);
        }
    }

    public function findById(Uuid $id): ?FlightAnnouncementConfig
    {
        return $this->entityManager->find(FlightAnnouncementConfig::class, $id);
    }

    public function findByFlightDefinitionId(Uuid $flightDefinitionId): array
    {
        return $this->entityManager
            ->getRepository(FlightAnnouncementConfig::class)
            ->findBy(['flightDefinitionId' => $flightDefinitionId], ['createdAt' => 'ASC']);
    }

    public function findOneForFlightAndType(Uuid $flightDefinitionId, FlightAnnouncementType $type): ?FlightAnnouncementConfig
    {
        return $this->entityManager
            ->getRepository(FlightAnnouncementConfig::class)
            ->findOneBy(['flightDefinitionId' => $flightDefinitionId, 'announcementType' => $type]);
    }
}
