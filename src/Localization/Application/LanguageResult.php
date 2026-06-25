<?php

declare(strict_types=1);

namespace App\Localization\Application;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LanguageResult',
    required: ['code', 'name', 'nativeName', 'active', 'sortOrder'],
)]
final readonly class LanguageResult
{
    public function __construct(
        #[OA\Property(example: 'ro-MD')]
        public string $code,
        #[OA\Property(example: 'Romanian (Moldova)')]
        public string $name,
        #[OA\Property(example: 'Romana')]
        public string $nativeName,
        public bool $active,
        #[OA\Property(minimum: 1)]
        public int $sortOrder,
    ) {
    }
}
