<?php

declare(strict_types=1);

namespace App\Announcements\Application\AddAnnouncementVariant;

final readonly class AddAnnouncementVariantCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $configId,
        public string $languageCode,
        public int $sortOrder,
        public string $sourceType,
        public ?string $audioAssetId,
        public ?string $text,
        public bool $enabled,
    ) {
    }
}
