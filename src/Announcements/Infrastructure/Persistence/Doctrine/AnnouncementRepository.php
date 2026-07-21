<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Announcement $announcement): void
    {
        $this->entityManager->persist($announcement);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?Announcement
    {
        return $this->entityManager->find(Announcement::class, $id);
    }

    public function findActiveContinuationByOccurrenceId(Uuid $flightOccurrenceId): ?Announcement
    {
        $announcements = $this->entityManager->createQueryBuilder()
            ->select('announcement')
            ->from(Announcement::class, 'announcement')
            ->where('announcement.flightOccurrenceId = :occurrenceId')
            ->andWhere('announcement.type = :type')
            ->andWhere('announcement.status = :status')
            ->andWhere('announcement.repeatEndedAt IS NULL')
            ->setParameter('occurrenceId', $flightOccurrenceId)
            ->setParameter('type', AnnouncementType::CheckInContinuation)
            ->setParameter('status', AnnouncementStatus::Prepared)
            ->orderBy('announcement.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getResult();

        return $announcements[0] ?? null;
    }

    public function findLatest(int $limit = 100): array
    {
        return $this->entityManager->getRepository(Announcement::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
        );
    }
}
