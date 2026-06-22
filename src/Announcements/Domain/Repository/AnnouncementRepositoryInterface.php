<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Repository;

use App\Announcements\Domain\Entity\Announcement;
use Symfony\Component\Uid\Uuid;

interface AnnouncementRepositoryInterface
{
    public function save(Announcement $announcement): void;

    public function findById(Uuid $id): ?Announcement;

    /**
     * @return list<Announcement>
     */
    public function findLatest(int $limit = 100): array;
}
