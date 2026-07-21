<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AudioAssetResult',
    required: ['id', 'name', 'languageCode', 'active', 'source'],
)]
final readonly class AudioAssetResult
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(example: 'gate-a12-en.wav')]
        public string $name,
        #[OA\Property(example: 'en')]
        public string $languageCode,
        public bool $active,
        #[OA\Property(nullable: true, example: 'audio/wav')]
        public ?string $mimeType,
        #[OA\Property(nullable: true, minimum: 1)]
        public ?int $sizeBytes,
        #[OA\Property(enum: ['uploaded', 'generated'], example: 'uploaded')]
        public string $source = 'uploaded',
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
            source: $asset->getSource()->value,
        );
    }
}
