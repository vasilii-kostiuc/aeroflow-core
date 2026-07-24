<?php

declare(strict_types=1);

namespace App\Announcements\Application\Support;

use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Domain\Exception\InvalidSupplementaryTemplateException;

/**
 * Cross-aggregate invariant shared by the create and update use cases: every
 * language variant of a supplementary template must reference an existing, active
 * AudioAsset. Announcements checks this through its own Audio Catalog port.
 */
final readonly class SupplementaryTemplateAssetGuard
{
    public function __construct(private AudioPromptLookupInterface $audioCatalog)
    {
    }

    /**
     * @param list<array<string,mixed>> $variants raw variants, possibly from untyped JSON
     */
    public function assertAvailable(array $variants): void
    {
        foreach ($variants as $variant) {
            $assetId = (string) ($variant['audioAssetId'] ?? '');
            if ('' === $assetId || !$this->audioCatalog->isActiveAsset($assetId)) {
                throw InvalidSupplementaryTemplateException::invalidAsset($assetId);
            }
        }
    }
}
