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
     * The active continuation announcement of an occurrence (task 020): a repeatable
     * type whose series has not yet ended. One in practice; used to end the repeat
     * on check-in close. Loaded for write so ending it cannot race its creation.
     */
    public function findActiveContinuationByOccurrenceId(Uuid $flightOccurrenceId): ?Announcement;

    /**
     * @return list<Announcement>
     */
    public function findLatest(int $limit = 100): array;
}
