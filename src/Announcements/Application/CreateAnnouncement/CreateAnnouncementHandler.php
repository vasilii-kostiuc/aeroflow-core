<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Exception\InactiveFlightDefinitionException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Announcements\Domain\ValueObject\CheckInCounterRange;
use App\Announcements\Domain\ValueObject\GateCode;
use App\FlightOperations\Domain\Exception\FlightDefinitionNotFoundException;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
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
        private FlightDefinitionRepositoryInterface $flightDefinitions,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateAnnouncementCommand $command): AnnouncementResult
    {
        if (!Uuid::isValid($command->flightDefinitionId)) {
            throw \App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException::forValue($command->flightDefinitionId);
        }

        $flightDefinition = $this->flightDefinitions->findById(Uuid::fromString($command->flightDefinitionId));
        if ($flightDefinition === null) {
            throw FlightDefinitionNotFoundException::withId($command->flightDefinitionId);
        }
        if (!$flightDefinition->isActive()) {
            throw InactiveFlightDefinitionException::withId($command->flightDefinitionId);
        }

        $languages = AnnouncementLanguages::fromCodes(...array_map(
            static fn (string $code): LanguageCode => LanguageCode::fromString($code),
            $command->languages,
        ));
        $type = AnnouncementType::from($command->type);
        $announcement = match ($type) {
            AnnouncementType::CheckInOpening => Announcement::openCheckIn(
                $command->flightDefinitionId,
                CheckInCounterRange::between((int) $command->checkInCounterStart, (int) $command->checkInCounterEnd),
                $languages,
            ),
            AnnouncementType::CheckInClosing => Announcement::closeCheckIn(
                $command->flightDefinitionId,
                CheckInCounterRange::between((int) $command->checkInCounterStart, (int) $command->checkInCounterEnd),
                $languages,
            ),
            AnnouncementType::BoardingInvitation => Announcement::inviteToBoard(
                $command->flightDefinitionId,
                GateCode::fromString((string) $command->gateCode),
                $languages,
            ),
            AnnouncementType::Arrival => Announcement::announceArrival($command->flightDefinitionId, $languages),
        };
        $this->repository->save($announcement);
        foreach ($announcement->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return AnnouncementResult::fromEntity($announcement);
    }
}
