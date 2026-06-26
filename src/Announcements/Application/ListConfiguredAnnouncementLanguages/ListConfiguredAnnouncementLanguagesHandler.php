<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListConfiguredAnnouncementLanguages;

use App\Announcements\Application\ConfiguredAnnouncementLanguagesResult;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListConfiguredAnnouncementLanguagesHandler
{
    public function __construct(
        private FlightAnnouncementConfigRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ListConfiguredAnnouncementLanguagesQuery $query): ConfiguredAnnouncementLanguagesResult
    {
        $type = FlightAnnouncementType::tryFrom($query->announcementType);
        if (!Uuid::isValid($query->flightDefinitionId) || null === $type) {
            return new ConfiguredAnnouncementLanguagesResult([]);
        }

        $config = $this->repository->findOneForFlightAndType(Uuid::fromString($query->flightDefinitionId), $type);
        if (null === $config || !$config->isEnabled()) {
            return new ConfiguredAnnouncementLanguagesResult([]);
        }

        $languages = [];
        foreach ($config->getVariants() as $variant) {
            if ($variant->isEnabled()) {
                $languages[] = $variant->getLanguageCode();
            }
        }

        return new ConfiguredAnnouncementLanguagesResult(array_values(array_unique($languages)));
    }
}
