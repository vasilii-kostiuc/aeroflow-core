<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Repository;

use App\AudioCatalog\Domain\Entity\AudioPrompt;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use Symfony\Component\Uid\Uuid;

interface AudioPromptRepositoryInterface
{
    public function save(AudioPrompt $prompt): void;

    public function findById(Uuid $id): ?AudioPrompt;

    public function findActive(AudioPromptKind $kind, string $value, string $languageCode): ?AudioPrompt;

    /** @return list<AudioPrompt> */
    public function findAll(?AudioPromptKind $kind = null, ?string $value = null, ?string $languageCode = null, ?bool $active = null): array;
}
