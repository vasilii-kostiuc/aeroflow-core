<?php

declare(strict_types=1);

namespace App\Announcements\Application\CancelAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Domain\Exception\AnnouncementNotFoundException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CancelAnnouncementHandler
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CancelAnnouncementCommand $command): AnnouncementResult
    {
        $announcement = $this->repository->findById($this->uuidParser->parse($command->id));
        if ($announcement === null) {
            throw AnnouncementNotFoundException::withId($command->id);
        }

        $announcement->cancel();
        $this->repository->save($announcement);
        $this->events->publish(...$announcement->pullEvents());

        return AnnouncementResult::fromEntity($announcement);
    }
}
