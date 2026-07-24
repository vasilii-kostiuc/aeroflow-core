<?php

declare(strict_types=1);

namespace App\Announcements\Application\CreateSupplementaryTemplate;

final readonly class CreateSupplementaryTemplateCommand
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
