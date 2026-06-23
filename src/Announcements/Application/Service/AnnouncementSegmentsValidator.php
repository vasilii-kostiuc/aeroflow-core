<?php

declare(strict_types=1);

namespace App\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;

final readonly class AnnouncementSegmentsValidator
{
    public function __construct(private AudioPromptLookupInterface $audioCatalog)
    {
    }

    /** @param list<array<string,mixed>> $segments */
    public function validate(array $segments): void
    {
        foreach ($segments as $segment) {
            if (($segment['type'] ?? null) !== 'audio_asset') {
                continue;
            }
            $id = (string) ($segment['audioAssetId'] ?? '');
            if (!$this->audioCatalog->isActiveAsset($id)) {
                throw AudioAssetUnavailableException::withId($id);
            }
        }
    }
}
