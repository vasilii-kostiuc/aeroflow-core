<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'LaunchOccurrenceAnnouncementRequest',
    required: ['languages'],
)]
final readonly class LaunchOccurrenceAnnouncementRequest
{
    /**
     * @param list<string> $languages
     * @param list<string> $checkInCounterIds
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['ro-MD', 'en'])]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type('string')])]
        public array $languages,
        #[OA\Property(
            description: 'Required for check-in announcements. IDs are resolved in the given order.',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'uuid'),
        )]
        #[Assert\All([new Assert\Uuid()])]
        public array $checkInCounterIds = [],
        #[OA\Property(description: 'Required for boarding.', format: 'uuid', nullable: true)]
        #[Assert\Uuid]
        public ?string $gateId = null,
    ) {
    }
}
