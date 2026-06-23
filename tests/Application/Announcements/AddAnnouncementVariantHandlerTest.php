<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\AddAnnouncementVariant\AddAnnouncementVariantCommand;
use App\Announcements\Application\AddAnnouncementVariant\AddAnnouncementVariantHandler;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\AudioCatalog\Domain\Exception\AudioAssetUnavailableException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class AddAnnouncementVariantHandlerTest extends TestCase
{
    public function testRejectsUnknownAudioAssetBeforeChangingAggregate(): void
    {
        $flightId = Uuid::v7();
        $config = FlightAnnouncementConfig::create(
            $flightId->toRfc4122(),
            FlightAnnouncementType::CheckInOpening,
            true,
            null,
        );
        $config->pullEvents();

        $configs = $this->createMock(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->method('findById')->willReturn($config);
        $configs->expects(self::never())->method('save');

        $assets = $this->createStub(AudioAssetRepositoryInterface::class);
        $assets->method('findById')->willReturn(null);

        $handler = new AddAnnouncementVariantHandler(
            $configs,
            $assets,
            $this->createStub(MessageBusInterface::class),
        );

        $this->expectException(AudioAssetUnavailableException::class);
        $handler(new AddAnnouncementVariantCommand(
            $flightId->toRfc4122(),
            $config->getId()->toRfc4122(),
            'ro-MD',
            1,
            'audio_asset',
            Uuid::v7()->toRfc4122(),
            null,
            true,
        ));
    }
}
