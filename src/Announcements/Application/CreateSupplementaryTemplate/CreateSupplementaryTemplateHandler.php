<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateSupplementaryTemplate;

use App\Announcements\Application\SupplementaryTemplateResult;
use App\Announcements\Application\Support\SupplementaryTemplateAssetGuard;
use App\Announcements\Domain\Entity\SupplementaryAnnouncementTemplate;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateSupplementaryTemplateHandler
{
    public function __construct(
        private SupplementaryAnnouncementTemplateRepositoryInterface $templates,
        private SupplementaryTemplateAssetGuard $assetGuard,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateSupplementaryTemplateCommand $command): SupplementaryTemplateResult
    {
        $this->assetGuard->assertAvailable($command->variants);

        $template = SupplementaryAnnouncementTemplate::create($command->name, $command->variants);
        $this->templates->save($template);
        $this->events->publish(...$template->pullEvents());

        return SupplementaryTemplateResult::fromEntity($template);
    }
}
