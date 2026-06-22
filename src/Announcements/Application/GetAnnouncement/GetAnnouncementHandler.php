<?php

declare(strict_types=1);

namespace App\Announcements\Application\GetAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Domain\Exception\AnnouncementNotFoundException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetAnnouncementHandler
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private UuidParser $uuidParser,
    ) {
    }

    public function __invoke(GetAnnouncementQuery $query): AnnouncementResult
    {
        $announcement = $this->repository->findById($this->uuidParser->parse($query->id));

        if ($announcement === null) {
            throw AnnouncementNotFoundException::withId($query->id);
        }

        return AnnouncementResult::fromEntity($announcement);
    }
}
