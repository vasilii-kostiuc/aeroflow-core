<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** Create and full-replace update of a folder; a missing `parentId` means top level. */
#[OA\Schema(schema: 'AudioFolderRequest', required: ['name'])]
final readonly class AudioFolderRequest
{
    public function __construct(
        #[OA\Property(example: 'SU 1234')]
        #[Assert\NotBlank]
        public string $name,
        #[OA\Property(format: 'uuid', nullable: true)]
        #[Assert\Uuid]
        public ?string $parentId = null,
    ) {
    }
}
