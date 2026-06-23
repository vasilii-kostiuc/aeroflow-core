<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application;

use App\AudioCatalog\Domain\Entity\AudioAsset;

final readonly class AudioAssetResult
{
    public function __construct(
        public string $id,
        public string $name,
        public string $languageCode,
        public bool $active,
        public ?string $mimeType,
        public ?int $sizeBytes,
    ) {
    }

    public static function fromEntity(AudioAsset $asset): self
    {
        return new self(
            id: $asset->getId()->toRfc4122(),
            name: $asset->getName(),
            languageCode: $asset->getLanguageCode(),
            active: $asset->isActive(),
            mimeType: $asset->getMimeType(),
            sizeBytes: $asset->getSizeBytes(),
        );
    }
}
