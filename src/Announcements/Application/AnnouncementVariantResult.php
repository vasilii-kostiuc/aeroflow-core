<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\AnnouncementTemplateSegment;
use App\Announcements\Domain\Entity\AnnouncementVariant;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnnouncementVariantResult',
    required: ['id', 'languageCode', 'sortOrder', 'segments', 'enabled', 'createdAt', 'updatedAt'],
)]
final readonly class AnnouncementVariantResult
{
    /** @param list<array<string, mixed>> $segments */
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(example: 'ro-MD')]
        public string $languageCode,
        #[OA\Property(minimum: 1)]
        public int $sortOrder,
        #[OA\Property(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'sortOrder', type: 'integer', minimum: 1),
                    new OA\Property(property: 'type', type: 'string', enum: ['audio_asset', 'dynamic_slot', 'pause', 'text']),
                    new OA\Property(property: 'audioAssetId', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'slot', type: 'string', enum: ['check_in_counters', 'gate_code'], nullable: true),
                    new OA\Property(property: 'durationMs', type: 'integer', minimum: 100, maximum: 10000, nullable: true),
                    new OA\Property(property: 'text', type: 'string', nullable: true),
                ],
                type: 'object',
            ),
        )]
        public array $segments,
        public bool $enabled,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(AnnouncementVariant $variant): self
    {
        return new self(
            $variant->getId()->toRfc4122(),
            $variant->getLanguageCode(),
            $variant->getSortOrder(),
            array_map(static fn (AnnouncementTemplateSegment $segment): array => [
                'id' => $segment->getId()->toRfc4122(),
                ...$segment->toArray(),
            ], $variant->getSegments()),
            $variant->isEnabled(),
            $variant->getCreatedAt()->format(DATE_ATOM),
            $variant->getUpdatedAt()->format(DATE_ATOM),
        );
    }
}
