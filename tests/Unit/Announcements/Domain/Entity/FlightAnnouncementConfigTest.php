<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\AnnouncementVariantSourceType;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Exception\DuplicateAnnouncementVariantLanguageException;
use App\Announcements\Domain\Exception\InvalidRepeatRuleException;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Event\AudioAssetUploaded;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class FlightAnnouncementConfigTest extends TestCase
{
    public function testValidatesDispatcherReadinessAndActiveLanguageUniqueness(): void
    {
        $config = FlightAnnouncementConfig::create(
            Uuid::v7()->toRfc4122(),
            FlightAnnouncementType::CheckInOpening,
            true,
            null,
        );

        self::assertSame(['no_active_variants'], $config->validationErrors());

        $config->addVariant(
            LanguageCode::fromString('ro-MD'),
            1,
            AnnouncementVariantSourceType::Text,
            null,
            'Înregistrarea este deschisă.',
            true,
        );

        self::assertSame([], $config->validationErrors());

        $config->addVariant(
            LanguageCode::fromString('ro-md'),
            2,
            AnnouncementVariantSourceType::Text,
            null,
            'Draft',
            false,
        );

        $this->expectException(DuplicateAnnouncementVariantLanguageException::class);
        $config->addVariant(
            LanguageCode::fromString('ro-MD'),
            3,
            AnnouncementVariantSourceType::Text,
            null,
            'Duplicate',
            true,
        );
    }

    public function testAllowsRepeatRuleOnlyForContinuationWithinRange(): void
    {
        $config = FlightAnnouncementConfig::create(
            Uuid::v7()->toRfc4122(),
            FlightAnnouncementType::CheckInContinuation,
            true,
            6,
        );

        self::assertSame(6, $config->getRepeatEveryMinutes());

        $this->expectException(InvalidRepeatRuleException::class);
        $config->changeSettings(true, 121);
    }

    public function testUploadedAudioAssetRecordsFileMetadataAndEvent(): void
    {
        $asset = AudioAsset::upload(
            'begin.wav',
            LanguageCode::fromString('ro-MD'),
            'generated.wav',
            'audio/x-wav',
            44,
        );

        self::assertSame('generated.wav', $asset->getStorageKey());
        self::assertSame('audio/x-wav', $asset->getMimeType());
        self::assertSame(44, $asset->getSizeBytes());
        self::assertInstanceOf(AudioAssetUploaded::class, $asset->pullEvents()[0]);
    }
}
