<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\AnnouncementVariant;

final readonly class AnnouncementVariantResult
{
    public function __construct(
        public string $id,
        public string $languageCode,
        public int $sortOrder,
        public string $sourceType,
        public ?string $audioAssetId,
        public ?string $text,
        public bool $enabled,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(AnnouncementVariant $variant): self
    {
        return new self(
            id: $variant->getId()->toRfc4122(),
            languageCode: $variant->getLanguageCode(),
            sortOrder: $variant->getSortOrder(),
            sourceType: $variant->getSourceType()->value,
            audioAssetId: $variant->getAudioAssetId()?->toRfc4122(),
            text: $variant->getText(),
            enabled: $variant->isEnabled(),
            createdAt: $variant->getCreatedAt()->format(DATE_ATOM),
            updatedAt: $variant->getUpdatedAt()->format(DATE_ATOM),
        );
    }
}
