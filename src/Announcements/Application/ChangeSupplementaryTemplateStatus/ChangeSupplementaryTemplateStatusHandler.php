<?php

declare(strict_types=1);

namespace App\Announcements\Application\ChangeSupplementaryTemplateStatus;

use App\Announcements\Application\SupplementaryTemplateResult;
use App\Announcements\Domain\Exception\SupplementaryTemplateNotFoundException;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangeSupplementaryTemplateStatusHandler
{
    public function __construct(
        private SupplementaryAnnouncementTemplateRepositoryInterface $templates,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(ChangeSupplementaryTemplateStatusCommand $command): SupplementaryTemplateResult
    {
        $template = $this->templates->findById($this->uuidParser->parse($command->id))
            ?? throw SupplementaryTemplateNotFoundException::withId($command->id);

        $command->active ? $template->activate() : $template->deactivate();
        $this->templates->save($template);
        $this->events->publish(...$template->pullEvents());

        return SupplementaryTemplateResult::fromEntity($template);
    }
}
