<?php

declare(strict_types=1);

namespace App\Tests\Integration\Announcements\Infrastructure\Persistence\Doctrine;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\AnnouncementVariantSourceType;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\Announcements\Domain\Repository\FlightAnnouncementConfigRepositoryInterface;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\FlightOperations\Domain\Entity\FlightDefinition;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\FlightOperations\Domain\Repository\FlightDefinitionRepositoryInterface;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FlightAnnouncementConfigRepositoryTest extends KernelTestCase
{
    public function testPersistsConfigWithOrderedTextAndAudioVariants(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $flights = $container->get(FlightDefinitionRepositoryInterface::class);
        $assets = $container->get(AudioAssetRepositoryInterface::class);
        $configs = $container->get(FlightAnnouncementConfigRepositoryInterface::class);

        $flight = FlightDefinition::create(
            FlightNumber::fromString('PC'.random_int(100, 999)),
            FlightDirection::Departure,
            AirportCode::fromString('RMO'),
            AirportCode::fromString('FCO'),
        );
        $flights->save($flight);

        $asset = AudioAsset::register('begin-en.wav', LanguageCode::fromString('en'));
        $assets->save($asset);

        $config = FlightAnnouncementConfig::create(
            $flight->getId()->toRfc4122(),
            FlightAnnouncementType::CheckInOpening,
            true,
            null,
        );
        $config->addVariant(
            LanguageCode::fromString('en'),
            2,
            AnnouncementVariantSourceType::AudioAsset,
            $asset->getId()->toRfc4122(),
            null,
            true,
        );
        $config->addVariant(
            LanguageCode::fromString('ro-MD'),
            1,
            AnnouncementVariantSourceType::Text,
            null,
            'Înregistrarea este deschisă.',
            true,
        );

        $configs->save($config);
        $persisted = $configs->findById($config->getId());

        self::assertNotNull($persisted);
        self::assertSame(['ro-MD', 'en'], array_map(
            static fn ($variant): string => $variant->getLanguageCode(),
            $persisted->getVariants(),
        ));
        self::assertSame($asset->getId()->toRfc4122(), $persisted->getVariants()[1]->getAudioAssetId()?->toRfc4122());
    }
}
