<?php

declare(strict_types=1);

namespace App\Announcements\Application\ChangeEmergencyTemplateStatus;

use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Domain\Exception\EmergencyTemplateNotFoundException;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangeEmergencyTemplateStatusHandler
{
    public function __construct(
        private EmergencyAnnouncementTemplateRepositoryInterface $templates,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(ChangeEmergencyTemplateStatusCommand $command): EmergencyTemplateResult
    {
        $template = $this->templates->findById($this->uuidParser->parse($command->id))
            ?? throw EmergencyTemplateNotFoundException::withId($command->id);

        $command->active ? $template->activate() : $template->deactivate();
        $this->templates->save($template);
        $this->events->publish(...$template->pullEvents());

        return EmergencyTemplateResult::fromEntity($template);
    }
}
