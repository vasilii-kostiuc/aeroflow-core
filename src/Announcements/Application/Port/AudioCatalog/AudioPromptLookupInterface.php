<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\AudioCatalog;

interface AudioPromptLookupInterface
{
    public function activeAssetId(string $kind, string $value, string $languageCode): ?string;

    public function isActiveAsset(string $audioAssetId): bool;
}
