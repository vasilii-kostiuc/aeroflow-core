<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\AudioCatalog;

use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AudioCatalogAudioPromptLookup implements AudioPromptLookupInterface
{
    public function __construct(
        private AudioPromptRepositoryInterface $prompts,
        private AudioAssetRepositoryInterface $assets,
    ) {
    }

    public function activeAssetId(string $kind, string $value, string $languageCode): ?string
    {
        $prompt = $this->prompts->findActive(AudioPromptKind::from($kind), $value, $languageCode);
        if ($prompt === null) {
            return null;
        }

        return $this->isActiveAsset($prompt->getAudioAssetId()->toRfc4122())
            ? $prompt->getAudioAssetId()->toRfc4122()
            : null;
    }

    public function isActiveAsset(string $audioAssetId): bool
    {
        if (!Uuid::isValid($audioAssetId)) {
            return false;
        }
        $asset = $this->assets->findById(Uuid::fromString($audioAssetId));

        return $asset !== null && $asset->isActive();
    }
}
