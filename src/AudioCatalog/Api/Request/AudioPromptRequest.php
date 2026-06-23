<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(schema: 'AudioPromptRequest', required: ['kind', 'value', 'languageCode', 'audioAssetId'])]
final readonly class AudioPromptRequest
{
    public function __construct(
        #[OA\Property(enum: ['check_in_counter_code', 'gate_code'], example: 'gate_code')]
        #[Assert\Choice(choices: ['check_in_counter_code', 'gate_code'])]
        public string $kind,
        #[OA\Property(example: 'A12')]
        #[Assert\NotBlank]
        public string $value,
        #[OA\Property(example: 'en')]
        #[Assert\NotBlank]
        public string $languageCode,
        #[OA\Property(format: 'uuid')]
        #[Assert\Uuid]
        public string $audioAssetId,
    ) {
    }
}
