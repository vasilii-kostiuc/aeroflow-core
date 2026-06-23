<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'AnnouncementVariantRequest',
    required: ['languageCode', 'sortOrder', 'segments', 'enabled'],
)]
final class AnnouncementVariantRequest
{
    #[OA\Property(example: 'ro-MD')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    public string $languageCode = '';

    #[OA\Property(minimum: 1, example: 1)]
    #[Assert\Positive]
    public int $sortOrder = 1;

    /** @var list<array<string, mixed>> */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(
            required: ['sortOrder', 'type'],
            properties: [
                new OA\Property(property: 'sortOrder', type: 'integer', minimum: 1, example: 1),
                new OA\Property(property: 'type', type: 'string', enum: ['audio_asset', 'dynamic_slot', 'pause', 'text']),
                new OA\Property(property: 'audioAssetId', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'slot', type: 'string', enum: ['check_in_counters', 'gate_code'], nullable: true),
                new OA\Property(property: 'durationMs', type: 'integer', minimum: 100, maximum: 10000, nullable: true),
                new OA\Property(property: 'text', type: 'string', nullable: true),
            ],
            type: 'object',
        ),
        example: [
            ['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => '01900000-0000-7000-8000-000000000010'],
            ['sortOrder' => 2, 'type' => 'dynamic_slot', 'slot' => 'check_in_counters'],
            ['sortOrder' => 3, 'type' => 'pause', 'durationMs' => 500],
        ],
    )]
    #[Assert\Count(min: 1)]
    public array $segments = [];

    #[OA\Property(example: true)]
    public bool $enabled = true;
}
