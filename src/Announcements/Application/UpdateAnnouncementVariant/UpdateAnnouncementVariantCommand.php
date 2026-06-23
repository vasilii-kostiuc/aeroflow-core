<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateAnnouncementVariant;

final readonly class UpdateAnnouncementVariantCommand
{
    /**
     * @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments
     */
    public function __construct(
        public string $flightDefinitionId,
        public string $configId,
        public string $variantId,
        public string $languageCode,
        public int $sortOrder,
        public array $segments,
        public bool $enabled,
    ) {
    }
}
