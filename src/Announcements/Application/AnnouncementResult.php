<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\Announcement;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnnouncementResult',
    required: [
        'id',
        'type',
        'flightDefinitionId',
        'checkInCounters',
        'languages',
        'audioSequence',
        'status',
        'createdAt',
    ],
)]
final readonly class AnnouncementResult
{
    /**
     * @param list<array{id:string,code:string}> $checkInCounters
     * @param array{id:string,code:string}|null  $gate
     * @param list<string>                       $languages
     * @param list<array<string,mixed>>          $audioSequence
     */
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(enum: ['check_in_opening', 'check_in_closing', 'boarding_invitation', 'arrival'])]
        public string $type,
        #[OA\Property(format: 'uuid')]
        public string $flightDefinitionId,
        #[OA\Property(format: 'uuid', nullable: true)]
        public ?string $flightOccurrenceId,
        #[OA\Property(
            type: 'array',
            items: new OA\Items(
                required: ['id', 'code'],
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'code', type: 'string'),
                ],
                type: 'object',
            ),
        )]
        public array $checkInCounters,
        #[OA\Property(
            required: ['id', 'code'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'code', type: 'string'),
            ],
            type: 'object',
            nullable: true,
        )]
        public ?array $gate,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['ro-MD', 'en'])]
        public array $languages,
        #[OA\Property(
            type: 'array',
            items: new OA\Items(
                required: ['languageCode', 'sortOrder', 'items'],
                properties: [
                    new OA\Property(property: 'languageCode', type: 'string'),
                    new OA\Property(property: 'sortOrder', type: 'integer', minimum: 1),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'type', type: 'string', enum: ['audio_asset', 'pause']),
                                new OA\Property(property: 'audioAssetId', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'durationMs', type: 'integer', minimum: 100, maximum: 10000, nullable: true),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
                type: 'object',
            ),
        )]
        public array $audioSequence,
        #[OA\Property(enum: ['pending_preparation', 'prepared', 'cancelled'])]
        public string $status,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time', nullable: true)]
        public ?string $cancelledAt,
    ) {
    }

    public static function fromEntity(Announcement $announcement): self
    {
        return new self(
            $announcement->getId()->toRfc4122(),
            $announcement->getType()->value,
            $announcement->getFlightDefinitionId()->toRfc4122(),
            $announcement->getFlightOccurrenceId()?->toRfc4122(),
            $announcement->getCheckInCounters(),
            $announcement->getGate(),
            $announcement->getLanguages()->toStrings(),
            $announcement->getAudioSequence(),
            $announcement->getStatus()->value,
            $announcement->getCreatedAt()->format(DATE_RFC3339),
            $announcement->getCancelledAt()?->format(DATE_RFC3339),
        );
    }
}
