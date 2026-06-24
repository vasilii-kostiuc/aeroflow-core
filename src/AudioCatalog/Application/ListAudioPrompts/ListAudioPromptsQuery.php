<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\ListAudioPrompts;

final readonly class ListAudioPromptsQuery
{
    public function __construct(
        public ?string $kind = null,
        public ?string $value = null,
        public ?string $languageCode = null,
        public ?bool $active = null,
    ) {
    }
}
