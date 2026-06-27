<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'StartNextManualFlightOccurrenceRequest',
    required: ['flightDefinitionId', 'operationalDate'],
)]
final readonly class StartNextManualFlightOccurrenceRequest
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        #[Assert\Uuid]
        public string $flightDefinitionId,
        #[OA\Property(format: 'date', example: '2026-06-25')]
        #[Assert\Date]
        public string $operationalDate,
    ) {
    }
}
