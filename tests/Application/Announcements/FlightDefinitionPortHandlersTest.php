<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\CreateAnnouncement\CreateAnnouncementCommand;
use App\Announcements\Application\CreateAnnouncement\CreateAnnouncementHandler;
use App\Announcements\Application\CreateFlightAnnouncementConfig\CreateFlightAnnouncementConfigCommand;
use App\Announcements\Application\CreateFlightAnnouncementConfig\CreateFlightAnnouncementConfigHandler;
use App\Announcements\Application\ListFlightAnnouncementConfigs\ListFlightAnnouncementConfigsHandler;
use App\Announcements\Application\ListFlightAnnouncementConfigs\ListFlightAnnouncementConfigsQuery;
use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionSnapshot;
use App\Announcements\Application\Port\FlightOperations\OperationalResourceLookupInterface;
use App\Announcements\Application\Service\AnnouncementTemplateResolver;
use App\Announcements\Application\UpdateFlightAnnouncementConfig\UpdateFlightAnnouncementConfigCommand;
use App\Announcements\Application\UpdateFlightAnnouncementConfig\UpdateFlightAnnouncementConfigHandler;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Enum\FlightDirection;
use App\Announcements\Domain\Exception\FlightDefinitionNotFoundException;
use App\Announcements\Domain\Exception\InactiveFlightDefinitionException;
use App\Announcements\Domain\Exception\IncompatibleFlightAnnouncementTypeException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class FlightDefinitionPortHandlersTest extends TestCase
{
    public function testCreatesAnnouncementForActiveFlightDefinition(): void
    {
        $flightId = Uuid::v7();
        $lookup = $this->lookup(new FlightDefinitionSnapshot(true, FlightDirection::Arrival));
        $announcements = $this->createMock(AnnouncementRepositoryInterface::class);
        $announcements->expects(self::once())->method('save');
        $config = FlightAnnouncementConfig::create($flightId->toRfc4122(), FlightAnnouncementType::Arrival, true, null);
        $assetId = Uuid::v7()->toRfc4122();
        $config->addVariant(\App\Shared\Domain\ValueObject\LanguageCode::fromString('en'), 1, [
            ['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => $assetId],
        ], true);
        $configs = $this->createStub(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->method('findOneForFlightAndType')->willReturn($config);
        $audio = $this->createStub(AudioPromptLookupInterface::class);
        $audio->method('isActiveAsset')->willReturn(true);

        $result = new CreateAnnouncementHandler(
            $announcements,
            $configs,
            $lookup,
            $this->createStub(OperationalResourceLookupInterface::class),
            new AnnouncementTemplateResolver($audio),
            $this->messageBus(),
        )(new CreateAnnouncementCommand(
            'arrival',
            $flightId->toRfc4122(),
            ['en'],
        ));

        self::assertSame($flightId->toRfc4122(), $result->flightDefinitionId);
    }

    public function testRejectsUnknownFlightDefinitionUsingAnnouncementsException(): void
    {
        $handler = new CreateAnnouncementHandler(
            $this->createStub(AnnouncementRepositoryInterface::class),
            $this->createStub(FlightAnnouncementConfigRepositoryInterface::class),
            $this->lookup(null),
            $this->createStub(OperationalResourceLookupInterface::class),
            new AnnouncementTemplateResolver($this->createStub(AudioPromptLookupInterface::class)),
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(FlightDefinitionNotFoundException::class);

        $handler(new CreateAnnouncementCommand(
            'arrival',
            Uuid::v7()->toRfc4122(),
            ['en'],
        ));
    }

    public function testRejectsInactiveFlightDefinition(): void
    {
        $handler = new CreateAnnouncementHandler(
            $this->createStub(AnnouncementRepositoryInterface::class),
            $this->createStub(FlightAnnouncementConfigRepositoryInterface::class),
            $this->lookup(new FlightDefinitionSnapshot(false, FlightDirection::Departure)),
            $this->createStub(OperationalResourceLookupInterface::class),
            new AnnouncementTemplateResolver($this->createStub(AudioPromptLookupInterface::class)),
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(InactiveFlightDefinitionException::class);

        $handler(new CreateAnnouncementCommand(
            'boarding_invitation',
            Uuid::v7()->toRfc4122(),
            ['en'],
            gateId: Uuid::v7()->toRfc4122(),
        ));
    }

    public function testCreateConfigRejectsTypeIncompatibleWithSnapshotDirection(): void
    {
        $configs = $this->createMock(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->expects(self::never())->method('save');
        $handler = new CreateFlightAnnouncementConfigHandler(
            $configs,
            $this->lookup(new FlightDefinitionSnapshot(true, FlightDirection::Arrival)),
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(IncompatibleFlightAnnouncementTypeException::class);

        $handler(new CreateFlightAnnouncementConfigCommand(
            Uuid::v7()->toRfc4122(),
            'boarding_invitation',
            true,
            null,
        ));
    }

    public function testUpdateConfigRejectsEnablingTypeIncompatibleWithSnapshotDirection(): void
    {
        $flightId = Uuid::v7();
        $config = FlightAnnouncementConfig::create(
            $flightId->toRfc4122(),
            FlightAnnouncementType::BoardingInvitation,
            false,
            null,
        );
        $config->pullEvents();
        $configs = $this->createMock(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->method('findById')->willReturn($config);
        $configs->expects(self::never())->method('save');
        $handler = new UpdateFlightAnnouncementConfigHandler(
            $configs,
            $this->lookup(new FlightDefinitionSnapshot(true, FlightDirection::Arrival)),
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(IncompatibleFlightAnnouncementTypeException::class);

        $handler(new UpdateFlightAnnouncementConfigCommand(
            $flightId->toRfc4122(),
            $config->getId()->toRfc4122(),
            true,
            null,
        ));
    }

    public function testListConfigUsesSnapshotDirectionForDispatcherValidation(): void
    {
        $flightId = Uuid::v7();
        $config = FlightAnnouncementConfig::create(
            $flightId->toRfc4122(),
            FlightAnnouncementType::Arrival,
            true,
            null,
        );
        $configs = $this->createStub(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->method('findByFlightDefinitionId')->willReturn([$config]);
        $handler = new ListFlightAnnouncementConfigsHandler(
            $configs,
            $this->lookup(new FlightDefinitionSnapshot(true, FlightDirection::Departure)),
        );

        $result = $handler(new ListFlightAnnouncementConfigsQuery($flightId->toRfc4122()));

        self::assertCount(1, $result);
        self::assertContains('announcement_type_incompatible_with_flight_direction', $result[0]->validationErrors);
    }

    private function lookup(?FlightDefinitionSnapshot $snapshot): FlightDefinitionLookupInterface
    {
        $lookup = $this->createStub(FlightDefinitionLookupInterface::class);
        $lookup->method('findById')->willReturn($snapshot);

        return $lookup;
    }

    private function messageBus(): MessageBusInterface
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            static fn (object $message): Envelope => new Envelope($message),
        );

        return $messageBus;
    }
}
