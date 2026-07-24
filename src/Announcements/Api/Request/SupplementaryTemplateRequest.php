<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'SupplementaryTemplateRequest',
    required: ['name', 'variants'],
)]
final class SupplementaryTemplateRequest
{
    #[OA\Property(example: 'Безопасность')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    /** @var list<array{languageCode:string,audioAssetId:string}> */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(
            required: ['languageCode', 'audioAssetId'],
            properties: [
                new OA\Property(property: 'languageCode', type: 'string', example: 'ro-MD'),
                new OA\Property(property: 'audioAssetId', type: 'string', format: 'uuid'),
            ],
            type: 'object',
        ),
    )]
    #[Assert\Count(min: 1)]
    public array $variants = [];
}
