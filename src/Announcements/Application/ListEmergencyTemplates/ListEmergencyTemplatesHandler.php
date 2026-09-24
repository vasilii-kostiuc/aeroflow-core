<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListEmergencyTemplates;

use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListEmergencyTemplatesHandler
{
    public function __construct(
        private EmergencyAnnouncementTemplateRepositoryInterface $templates,
    ) {
    }

    /**
     * @return list<EmergencyTemplateResult>
     */
    public function __invoke(ListEmergencyTemplatesQuery $query): array
    {
        return array_map(
            EmergencyTemplateResult::fromEntity(...),
            $this->templates->findAll($query->active),
        );
    }
}
