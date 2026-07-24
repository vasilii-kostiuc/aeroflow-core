<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateSupplementaryTemplate;

use App\Announcements\Application\SupplementaryTemplateResult;
use App\Announcements\Application\Support\SupplementaryTemplateAssetGuard;
use App\Announcements\Domain\Exception\SupplementaryTemplateNotFoundException;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateSupplementaryTemplateHandler
{
    public function __construct(
        private SupplementaryAnnouncementTemplateRepositoryInterface $templates,
        private SupplementaryTemplateAssetGuard $assetGuard,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UpdateSupplementaryTemplateCommand $command): SupplementaryTemplateResult
    {
        $template = $this->templates->findById($this->uuidParser->parse($command->id))
            ?? throw SupplementaryTemplateNotFoundException::withId($command->id);

        $this->assetGuard->assertAvailable($command->variants);

        $template->update($command->name, $command->variants);
        $this->templates->save($template);
        $this->events->publish(...$template->pullEvents());

        return SupplementaryTemplateResult::fromEntity($template);
    }
}
