<?php

declare(strict_types=1);

namespace App\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\AudioAssetAvailabilityInterface;
use App\Announcements\Domain\Enum\AnnouncementVariantSourceType;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use Symfony\Component\Uid\Uuid;

final readonly class AnnouncementAudioAssetValidator
{
    public function __construct(private AudioAssetAvailabilityInterface $audioAssets)
    {
    }

    public function validate(string $sourceType, ?string $audioAssetId): void
    {
        if (AnnouncementVariantSourceType::AudioAsset->value !== $sourceType) {
            return;
        }

        if ($audioAssetId === null || !Uuid::isValid($audioAssetId)) {
            throw AudioAssetUnavailableException::withId((string) $audioAssetId);
        }

        $id = Uuid::fromString($audioAssetId);
        if (!$this->audioAssets->isAvailable($id)) {
            throw AudioAssetUnavailableException::withId($audioAssetId);
        }
    }
}
