<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateEmergencyTemplate;

use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Application\Support\EmergencyTemplateAssetGuard;
use App\Announcements\Domain\Exception\EmergencyTemplateNotFoundException;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateEmergencyTemplateHandler
{
    public function __construct(
        private EmergencyAnnouncementTemplateRepositoryInterface $templates,
        private EmergencyTemplateAssetGuard $assetGuard,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UpdateEmergencyTemplateCommand $command): EmergencyTemplateResult
    {
        $template = $this->templates->findById($this->uuidParser->parse($command->id))
            ?? throw EmergencyTemplateNotFoundException::withId($command->id);

        $this->assetGuard->assertAvailable($command->variants);

        $template->update($command->name, $command->variants);
        $this->templates->save($template);
        $this->events->publish(...$template->pullEvents());

        return EmergencyTemplateResult::fromEntity($template);
    }
}
