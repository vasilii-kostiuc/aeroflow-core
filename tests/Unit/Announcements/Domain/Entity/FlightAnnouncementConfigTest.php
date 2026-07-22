<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\DuplicateAnnouncementVariantLanguageException;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FlightAnnouncementConfigTest extends TestCase
{
    public function testStoresOrderedMixedSegmentsAndValidatesReadiness(): void
    {
        $config = FlightAnnouncementConfig::create(Uuid::v7()->toRfc4122(), FlightAnnouncementType::CheckInOpening, true, null);
        $variant = $config->addVariant(LanguageCode::fromString('en'), 1, [
            ['sortOrder' => 2, 'type' => 'dynamic_slot', 'slot' => 'check_in_counters'],
            ['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()],
            ['sortOrder' => 3, 'type' => 'pause', 'durationMs' => 500],
        ], true);

        [$audioAsset, $dynamicSlot, $pause] = $variant->getSegments();
        self::assertSame(['audio_asset', 'dynamic_slot', 'pause'], array_map(static fn ($segment): string => $segment->getType()->value, [$audioAsset, $dynamicSlot, $pause]));
        self::assertNotNull($audioAsset->getAudioAssetId());
        self::assertSame('check_in_counters', $dynamicSlot->getSlot()?->value);
        self::assertSame(500, $pause->getDurationMs());
        self::assertSame([], $config->validationErrors());
    }

    public function testTextSegmentRequiresTts(): void
    {
        $config = FlightAnnouncementConfig::create(Uuid::v7()->toRfc4122(), FlightAnnouncementType::Arrival, true, null);
        $variant = $config->addVariant(LanguageCode::fromString('en'), 1, [
            ['sortOrder' => 1, 'type' => 'text', 'text' => '  Arrived  '],
        ], true);
        self::assertSame('Arrived', $variant->getSegments()[0]->getText());
        self::assertContains('text_segment_requires_tts', $config->validationErrors());
    }

    public function testTextSegmentWithResolvedAssetIsReady(): void
    {
        $assetId = Uuid::v7()->toRfc4122();
        $config = FlightAnnouncementConfig::create(Uuid::v7()->toRfc4122(), FlightAnnouncementType::Arrival, true, null);
        $variant = $config->addVariant(LanguageCode::fromString('en'), 1, [
            ['sortOrder' => 1, 'type' => 'text', 'text' => 'Arrived', 'audioAssetId' => $assetId],
        ], true);

        self::assertSame($assetId, $variant->getSegments()[0]->getAudioAssetId()?->toRfc4122());
        self::assertSame('Arrived', $variant->getSegments()[0]->getText());
        self::assertSame([], $config->validationErrors());
    }

    public function testActiveLanguageMustBeUnique(): void
    {
        $config = FlightAnnouncementConfig::create(Uuid::v7()->toRfc4122(), FlightAnnouncementType::Arrival, true, null);
        $segments = [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()]];
        $config->addVariant(LanguageCode::fromString('en'), 1, $segments, true);
        $this->expectException(DuplicateAnnouncementVariantLanguageException::class);
        $config->addVariant(LanguageCode::fromString('en'), 2, $segments, true);
    }
}
