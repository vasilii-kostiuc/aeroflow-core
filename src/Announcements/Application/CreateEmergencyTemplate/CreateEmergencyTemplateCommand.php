<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateEmergencyTemplate;

final readonly class CreateEmergencyTemplateCommand
{
    /**
     * @param list<array{languageCode:string,audioAssetId:string}> $variants
     */
    public function __construct(
        public string $name,
        public array $variants,
    ) {
    }
}
