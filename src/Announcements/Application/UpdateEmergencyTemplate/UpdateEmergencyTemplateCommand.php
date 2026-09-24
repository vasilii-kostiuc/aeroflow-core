<?php

declare(strict_types=1);

namespace App\Announcements\Application\UpdateEmergencyTemplate;

final readonly class UpdateEmergencyTemplateCommand
{
    /**
     * @param list<array{languageCode:string,audioAssetId:string}> $variants
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $variants,
    ) {
    }
}
