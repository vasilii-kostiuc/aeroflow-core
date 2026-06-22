<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
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

    public function findLatest(int $limit = 100): array
    {
        return $this->entityManager->getRepository(Announcement::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
        );
    }
}
