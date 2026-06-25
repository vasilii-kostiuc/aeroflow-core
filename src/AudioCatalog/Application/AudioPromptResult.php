<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application;

use App\AudioCatalog\Domain\Entity\AudioPrompt;
use DateTimeInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AudioPromptResult',
    required: ['id', 'kind', 'value', 'languageCode', 'audioAssetId', 'active', 'createdAt', 'updatedAt'],
)]
final readonly class AudioPromptResult
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(enum: ['check_in_counter_code', 'gate_code'], example: 'gate_code')]
        public string $kind,
        #[OA\Property(example: 'A12')]
        public string $value,
        #[OA\Property(example: 'en')]
        public string $languageCode,
        #[OA\Property(format: 'uuid')]
        public string $audioAssetId,
        public bool $active,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(AudioPrompt $prompt): self
    {
        return new self(
            id: $prompt->getId()->toRfc4122(),
            kind: $prompt->getKind()->value,
            value: $prompt->getValue(),
            languageCode: $prompt->getLanguageCode(),
            audioAssetId: $prompt->getAudioAssetId()->toRfc4122(),
            active: $prompt->isActive(),
            createdAt: $prompt->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $prompt->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
