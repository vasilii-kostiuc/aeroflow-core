<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Support;

use App\AudioCatalog\Domain\Exception\AudioPromptAssetUnavailableException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Cross-aggregate invariant shared by the create and update use cases: an
 * AudioPrompt may only reference an existing, active AudioAsset.
 */
final readonly class AudioPromptAssetGuard
{
    public function __construct(private AudioAssetRepositoryInterface $assets)
    {
    }

    public function assertAvailable(string $assetId): void
    {
        if (!Uuid::isValid($assetId)) {
            throw AudioPromptAssetUnavailableException::withId($assetId);
        }

        $asset = $this->assets->findById(Uuid::fromString($assetId));
        if ($asset === null || !$asset->isActive()) {
            throw AudioPromptAssetUnavailableException::withId($assetId);
        }
    }
}
