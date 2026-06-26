<?php

declare(strict_types=1);

namespace App\FlightOperations\Application\LaunchOccurrenceAnnouncement;

use App\FlightOperations\Application\FlightOccurrenceResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LaunchOccurrenceAnnouncementResult',
    required: ['occurrence', 'announcementId'],
)]
final readonly class LaunchOccurrenceAnnouncementResult
{
    public function __construct(
        #[OA\Property(ref: new Model(type: FlightOccurrenceResult::class))]
        public FlightOccurrenceResult $occurrence,
        #[OA\Property(format: 'uuid')]
        public string $announcementId,
    ) {
    }
}
