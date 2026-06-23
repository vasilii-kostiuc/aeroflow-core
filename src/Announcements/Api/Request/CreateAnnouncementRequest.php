<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateAnnouncementRequest',
    required: ['type', 'flightDefinitionId', 'languages'],
)]
final readonly class CreateAnnouncementRequest
{
    /**
     * @param list<string> $languages
     * @param list<string> $checkInCounterIds
     */
    public function __construct(
        #[OA\Property(enum: ['check_in_opening', 'check_in_closing', 'boarding_invitation', 'arrival'], example: 'check_in_opening')]
        #[Assert\Choice(choices: ['check_in_opening', 'check_in_closing', 'boarding_invitation', 'arrival'])]
        public string $type,
        #[OA\Property(format: 'uuid')]
        #[Assert\Uuid]
        public string $flightDefinitionId,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['ro-MD', 'en'])]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type('string')])]
        public array $languages,
        #[OA\Property(
            description: 'Required for check-in announcements. IDs are resolved in the given order.',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'uuid'),
            example: ['01900000-0000-7000-8000-000000000001', '01900000-0000-7000-8000-000000000003'],
        )]
        #[Assert\All([new Assert\Uuid()])]
        public array $checkInCounterIds = [],
        #[OA\Property(description: 'Required for boarding_invitation.', format: 'uuid', nullable: true)]
        #[Assert\Uuid]
        public ?string $gateId = null,
    ) {
    }
}
