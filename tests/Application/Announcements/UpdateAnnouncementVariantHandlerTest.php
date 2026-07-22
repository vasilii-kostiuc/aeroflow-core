<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Application\Port\AudioCatalog\SpeechAssetGeneratorInterface;
use App\Announcements\Application\Service\AnnouncementSegmentsValidator;
use App\Announcements\Application\Service\TextSegmentSpeechResolver;
use App\Announcements\Application\UpdateAnnouncementVariant\UpdateAnnouncementVariantCommand;
use App\Announcements\Application\UpdateAnnouncementVariant\UpdateAnnouncementVariantHandler;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAnnouncementVariantHandlerTest extends TestCase
{
    public function testGeneratesSpeechWhenAVariantGainsATextSegment(): void
    {
        $flightId = Uuid::v7();
        $assetId = Uuid::v7()->toRfc4122();
        $config = FlightAnnouncementConfig::create($flightId->toRfc4122(), FlightAnnouncementType::Arrival, true, null);
        $variant = $config->addVariant(LanguageCode::fromString('en'), 1, [
            ['sortOrder' => 1, 'type' => 'pause', 'durationMs' => 500],
        ], true);
        $config->pullEvents();

        $configs = $this->createMock(FlightAnnouncementConfigRepositoryInterface::class);
        $configs->method('findById')->willReturn($config);
        $configs->expects(self::once())->method('save');

        $generator = $this->createMock(SpeechAssetGeneratorInterface::class);
        $generator->expects(self::once())
            ->method('generate')
            ->with('Please board', 'en')
            ->willReturn($assetId);

        $handler = new UpdateAnnouncementVariantHandler(
            $configs,
            new AnnouncementSegmentsValidator($this->createStub(AudioPromptLookupInterface::class)),
            new TextSegmentSpeechResolver($generator),
            $this->createStub(DomainEventPublisher::class),
        );

        $handler(new UpdateAnnouncementVariantCommand(
            $flightId->toRfc4122(),
            $config->getId()->toRfc4122(),
            $variant->getId()->toRfc4122(),
            'en',
            1,
            [['sortOrder' => 1, 'type' => 'text', 'text' => 'Please board']],
            true,
        ));

        $segment = $config->getVariants()[0]->getSegments()[0];
        self::assertSame('text', $segment->getType()->value);
        self::assertSame($assetId, $segment->getAudioAssetId()?->toRfc4122());
    }
}
