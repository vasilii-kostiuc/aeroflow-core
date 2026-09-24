<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** Group move; a missing `folderId` means «Без папки». */
#[OA\Schema(schema: 'MoveAudioAssetsRequest', required: ['assetIds'])]
final readonly class MoveAudioAssetsRequest
{
    /**
     * @param list<string> $assetIds
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', format: 'uuid'))]
        #[Assert\Count(min: 1, max: 500)]
        #[Assert\All([new Assert\Uuid()])]
        public array $assetIds,
        #[OA\Property(format: 'uuid', nullable: true)]
        #[Assert\Uuid]
        public ?string $folderId = null,
    ) {
    }
}
