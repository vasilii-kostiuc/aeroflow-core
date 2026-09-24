<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AudioFolderResult', required: ['id', 'name', 'parentId'])]
final readonly class AudioFolderResult
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(example: 'Вылеты')]
        public string $name,
        #[OA\Property(format: 'uuid', nullable: true)]
        public ?string $parentId,
    ) {
    }

    public static function fromEntity(AudioFolder $folder): self
    {
        return new self(
            id: $folder->getId()->toRfc4122(),
            name: $folder->getName(),
            parentId: $folder->getParentId()?->toRfc4122(),
        );
    }
}
