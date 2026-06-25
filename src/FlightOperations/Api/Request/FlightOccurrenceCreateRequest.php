<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'FlightOccurrenceCreateRequest',
    required: ['flightDefinitionId', 'operationalDate'],
)]
final readonly class FlightOccurrenceCreateRequest
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        #[Assert\Uuid]
        public string $flightDefinitionId,
        #[OA\Property(format: 'date', example: '2026-06-25')]
        #[Assert\Date]
        public string $operationalDate,
        #[OA\Property(minimum: 1, example: 1)]
        #[Assert\Positive]
        public int $sequenceNumber = 1,
        #[OA\Property(enum: ['manual', 'schedule'], example: 'manual')]
        #[Assert\Choice(choices: ['manual', 'schedule'])]
        public string $source = 'manual',
    ) {
    }
}
