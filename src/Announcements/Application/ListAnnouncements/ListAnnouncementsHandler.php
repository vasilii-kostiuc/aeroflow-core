<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListAnnouncements;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListAnnouncementsHandler
{
    public function __construct(private AnnouncementRepositoryInterface $repository)
    {
    }

    /**
     * @return list<AnnouncementResult>
     */
    public function __invoke(ListAnnouncementsQuery $query): array
    {
        return array_map(
            AnnouncementResult::fromEntity(...),
            $this->repository->findLatest(max(1, min(100, $query->limit))),
        );
    }
}
