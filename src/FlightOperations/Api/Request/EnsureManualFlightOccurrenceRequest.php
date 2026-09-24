<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * No operational date: the server takes today's operational day of the airport
 * (task 027); a stray `operationalDate` in the body is ignored.
 */
#[OA\Schema(
    schema: 'EnsureManualFlightOccurrenceRequest',
    required: ['flightDefinitionId'],
)]
final readonly class EnsureManualFlightOccurrenceRequest
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        #[Assert\Uuid]
        public string $flightDefinitionId,
        #[OA\Property(minimum: 1, example: 1)]
        #[Assert\Positive]
        public int $sequenceNumber = 1,
    ) {
    }
}
