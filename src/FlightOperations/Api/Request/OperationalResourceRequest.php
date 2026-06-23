<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(schema: 'OperationalResourceRequest', required: ['code', 'displayName', 'sortOrder'])]
final readonly class OperationalResourceRequest
{
    public function __construct(
        #[OA\Property(description: 'Numeric or composite code.', example: 'A12')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 16)]
        public string $code,
        #[OA\Property(example: 'Gate A12')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 128)]
        public string $displayName,
        #[OA\Property(minimum: 1, example: 1)]
        #[Assert\Positive]
        public int $sortOrder = 1,
    ) {
    }
}
