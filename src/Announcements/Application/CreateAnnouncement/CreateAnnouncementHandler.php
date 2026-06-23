<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Application\Port\FlightOperations\OperationalResourceLookupInterface;
use App\Announcements\Application\Service\AnnouncementTemplateResolver;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\AnnouncementConfigurationNotReadyException;
use App\Announcements\Domain\Exception\FlightDefinitionNotFoundException;
use App\Announcements\Domain\Exception\InactiveFlightDefinitionException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\Exception\OperationalResourceUnavailableException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAnnouncementHandler
{
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private FlightAnnouncementConfigRepositoryInterface $configs,
        private FlightDefinitionLookupInterface $flightDefinitions,
        private OperationalResourceLookupInterface $resources,
        private AnnouncementTemplateResolver $resolver,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
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

        $counters = [];
        $gate = null;
        if (in_array($type, [AnnouncementType::CheckInOpening, AnnouncementType::CheckInClosing], true)) {
            if ($command->checkInCounterIds === [] || count($command->checkInCounterIds) !== count(array_unique($command->checkInCounterIds))) {
                throw OperationalResourceUnavailableException::counters($command->checkInCounterIds);
            }
            $counters = $this->resources->resolveActiveCheckInCounters($command->checkInCounterIds);
            if (count($counters) !== count($command->checkInCounterIds)) {
                throw OperationalResourceUnavailableException::counters($command->checkInCounterIds);
            }
        } elseif ($type === AnnouncementType::BoardingInvitation) {
            if ($command->gateId === null || ($gate = $this->resources->resolveActiveGate($command->gateId)) === null) {
                throw OperationalResourceUnavailableException::gate((string) $command->gateId);
            }
        }

        $audioSequence = $this->resolver->resolve($config, $languages->toStrings(), $counters, $gate);
        $announcement = Announcement::createPrepared(
            $type,
            $command->flightDefinitionId,
            $languages,
            array_map(static fn ($counter): array => ['id' => $counter->id, 'code' => $counter->code], $counters),
            $gate === null ? null : ['id' => $gate->id, 'code' => $gate->code],
            $audioSequence,
        );
        $this->repository->save($announcement);
        foreach ($announcement->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return AnnouncementResult::fromEntity($announcement);
    }
}
