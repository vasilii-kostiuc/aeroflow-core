<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Application\Service\AnnouncementOperationalResourceResolver;
use App\Announcements\Application\Service\AnnouncementTemplateResolver;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\AnnouncementConfigurationNotReadyException;
use App\Announcements\Domain\Exception\FlightDefinitionNotFoundException;
use App\Announcements\Domain\Exception\InactiveFlightDefinitionException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAnnouncementHandler
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private FlightAnnouncementConfigRepositoryInterface $configs,
        private FlightDefinitionLookupInterface $flightDefinitions,
        private AnnouncementOperationalResourceResolver $resourceResolver,
        private AnnouncementTemplateResolver $resolver,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateAnnouncementCommand $command): AnnouncementResult
    {
        if (!Uuid::isValid($command->flightDefinitionId)) {
            throw InvalidFlightDefinitionIdException::forValue($command->flightDefinitionId);
        }
        $flightId = Uuid::fromString($command->flightDefinitionId);
        $flight = $this->flightDefinitions->findById($flightId);
        if ($flight === null) {
            throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);
        }
        if (!$flight->active) {
            throw InactiveFlightDefinitionException::withId($command->flightDefinitionId);
        }

        $type = AnnouncementType::from($command->type);
        $config = $this->configs->findOneForFlightAndType($flightId, FlightAnnouncementType::from($type->value))
            ?? throw AnnouncementConfigurationNotReadyException::withErrors(['configuration_not_found']);
        $languages = AnnouncementLanguages::fromCodes(...array_map(
            static fn (string $code): LanguageCode => LanguageCode::fromString($code),
            $command->languages,
        ));

        $resources = $this->resourceResolver->resolve($type, $command->checkInCounterIds, $command->gateId);

        $audioSequence = $this->resolver->resolve(
            $config,
            $languages->toStrings(),
            $resources->checkInCounters,
            $resources->gate,
        );
        $announcement = Announcement::createPrepared(
            $type,
            $command->flightDefinitionId,
            $languages,
            $resources->checkInCounterSnapshots(),
            $resources->gateSnapshot(),
            $audioSequence,
        );
        $this->repository->save($announcement);
        $this->events->publish(...$announcement->pullEvents());

        return AnnouncementResult::fromEntity($announcement);
    }
}
