<?php

declare(strict_types=1);

namespace App\Announcements\Application\ChangeAnnouncementLanguages;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Domain\Exception\AnnouncementNotFoundException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Application\Uuid\UuidParser;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangeAnnouncementLanguagesHandler
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private UuidParser $uuidParser,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(ChangeAnnouncementLanguagesCommand $command): AnnouncementResult
    {
        $announcement = $this->repository->findById($this->uuidParser->parse($command->id));
        if ($announcement === null) {
            throw AnnouncementNotFoundException::withId($command->id);
        }

        $announcement->changeLanguages(AnnouncementLanguages::fromCodes(...array_map(
            static fn (string $code): LanguageCode => LanguageCode::fromString($code),
            $command->languages,
        )));
        $this->repository->save($announcement);
        foreach ($announcement->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return AnnouncementResult::fromEntity($announcement);
    }
}
