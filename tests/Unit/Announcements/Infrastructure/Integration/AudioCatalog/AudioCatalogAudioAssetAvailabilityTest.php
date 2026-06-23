<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Infrastructure\Integration\AudioCatalog;

use App\Announcements\Infrastructure\Integration\AudioCatalog\AudioCatalogAudioAssetAvailability;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AudioCatalogAudioAssetAvailabilityTest extends TestCase
{
    public function testReturnsTrueForActiveAudioAsset(): void
    {
        $audioAsset = AudioAsset::register('boarding.wav', LanguageCode::fromString('en'));
        $repository = $this->createMock(AudioAssetRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findById')
            ->with($audioAsset->getId())
            ->willReturn($audioAsset);

        self::assertTrue(
            new AudioCatalogAudioAssetAvailability($repository)->isAvailable($audioAsset->getId()),
        );
    }

    public function testReturnsFalseForUnknownAudioAsset(): void
    {
        $id = Uuid::v7();
        $repository = $this->createMock(AudioAssetRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        self::assertFalse(new AudioCatalogAudioAssetAvailability($repository)->isAvailable($id));
    }
}
