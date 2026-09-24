<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'EmergencyTemplateRequest',
    required: ['name', 'variants'],
)]
final class EmergencyTemplateRequest
{
    #[OA\Property(example: 'Пожарная тревога')]
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
