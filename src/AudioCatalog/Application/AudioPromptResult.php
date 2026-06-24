<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application;

use App\AudioCatalog\Domain\Entity\AudioPrompt;
use DateTimeInterface;

final readonly class AudioPromptResult
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $value,
        public string $languageCode,
        public string $audioAssetId,
        public bool $active,
        public string $createdAt,
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
