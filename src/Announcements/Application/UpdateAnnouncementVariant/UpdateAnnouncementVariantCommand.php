<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateAnnouncementVariant;

final readonly class UpdateAnnouncementVariantCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $configId,
        public string $variantId,
        public string $languageCode,
        public int $sortOrder,
        public string $sourceType,
        public ?string $audioAssetId,
        public ?string $text,
        public bool $enabled,
    ) {
    }
}
