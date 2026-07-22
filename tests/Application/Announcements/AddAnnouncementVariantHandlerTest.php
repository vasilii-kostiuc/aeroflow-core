<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\AddAnnouncementVariant\AddAnnouncementVariantCommand;
use App\Announcements\Application\AddAnnouncementVariant\AddAnnouncementVariantHandler;
use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Application\Port\AudioCatalog\SpeechAssetGeneratorInterface;
use App\Announcements\Application\Service\AnnouncementSegmentsValidator;
use App\Announcements\Application\Service\TextSegmentSpeechResolver;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;
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

        $assets = $this->createStub(AudioPromptLookupInterface::class);
        $assets->method('isActiveAsset')->willReturn(false);

        $handler = new AddAnnouncementVariantHandler(
            $configs,
            new AnnouncementSegmentsValidator($assets),
            new TextSegmentSpeechResolver($this->createStub(SpeechAssetGeneratorInterface::class)),
            $this->createStub(DomainEventPublisher::class),
        );

        $this->expectException(AudioAssetUnavailableException::class);
        $handler(new AddAnnouncementVariantCommand(
            $flightId->toRfc4122(),
            $config->getId()->toRfc4122(),
            'ro-MD',
            1,
            [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()]],
            true,
        ));
    }

    public function testGeneratesSpeechForTextSegmentAndStoresAssetId(): void
    {
        $flightId = Uuid::v7();
        $assetId = Uuid::v7()->toRfc4122();
        $config = FlightAnnouncementConfig::create($flightId->toRfc4122(), FlightAnnouncementType::Arrival, true, null);
        $config->pullEvents();

        $configs = $this->createMock(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->method('findById')->willReturn($config);
        $configs->expects(self::once())->method('save');

        $generator = $this->createMock(SpeechAssetGeneratorInterface::class);
        $generator->expects(self::once())
            ->method('generate')
            ->with('Flight arrived', 'en')
            ->willReturn($assetId);

        $handler = new AddAnnouncementVariantHandler(
            $configs,
            new AnnouncementSegmentsValidator($this->createStub(AudioPromptLookupInterface::class)),
            new TextSegmentSpeechResolver($generator),
            $this->createStub(DomainEventPublisher::class),
        );

        $handler(new AddAnnouncementVariantCommand(
            $flightId->toRfc4122(),
            $config->getId()->toRfc4122(),
            'en',
            1,
            [['sortOrder' => 1, 'type' => 'text', 'text' => 'Flight arrived']],
            true,
        ));

        $segment = $config->getVariants()[0]->getSegments()[0];
        self::assertSame('text', $segment->getType()->value);
        self::assertSame($assetId, $segment->getAudioAssetId()?->toRfc4122());
        self::assertSame('Flight arrived', $segment->getText());
    }
}
