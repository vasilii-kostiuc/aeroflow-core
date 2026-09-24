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
    schema: 'StartNextManualFlightOccurrenceRequest',
    required: ['flightDefinitionId'],
)]
final readonly class StartNextManualFlightOccurrenceRequest
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        #[Assert\Uuid]
        public string $flightDefinitionId,
    ) {
    }
}
