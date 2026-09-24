<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateEmergencyTemplate;

use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Application\Support\EmergencyTemplateAssetGuard;
use App\Announcements\Domain\Entity\EmergencyAnnouncementTemplate;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateEmergencyTemplateHandler
{
    public function __construct(
        private EmergencyAnnouncementTemplateRepositoryInterface $templates,
        private EmergencyTemplateAssetGuard $assetGuard,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateEmergencyTemplateCommand $command): EmergencyTemplateResult
    {
        $this->assetGuard->assertAvailable($command->variants);

        $template = EmergencyAnnouncementTemplate::create($command->name, $command->variants);
        $this->templates->save($template);
        $this->events->publish(...$template->pullEvents());

        return EmergencyTemplateResult::fromEntity($template);
    }
}
