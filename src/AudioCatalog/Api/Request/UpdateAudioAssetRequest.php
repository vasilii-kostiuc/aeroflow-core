<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** Full replace of an asset's library details; a missing `folderId` means «Без папки». */
#[OA\Schema(schema: 'UpdateAudioAssetRequest', required: ['title'])]
final readonly class UpdateAudioAssetRequest
{
    public function __construct(
        #[OA\Property(example: 'Выход A12')]
        #[Assert\NotBlank]
        public string $title,
        #[OA\Property(format: 'uuid', nullable: true)]
        #[Assert\Uuid]
        public ?string $folderId = null,
    ) {
    }
}
