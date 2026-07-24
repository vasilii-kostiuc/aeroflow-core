<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListSupplementaryTemplates;

use App\Announcements\Application\SupplementaryTemplateResult;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListSupplementaryTemplatesHandler
{
    public function __construct(
        private SupplementaryAnnouncementTemplateRepositoryInterface $templates,
    ) {
    }

    /**
     * @return list<SupplementaryTemplateResult>
     */
    public function __invoke(ListSupplementaryTemplatesQuery $query): array
    {
        return array_map(
            SupplementaryTemplateResult::fromEntity(...),
            $this->templates->findAll($query->active),
        );
    }
}
