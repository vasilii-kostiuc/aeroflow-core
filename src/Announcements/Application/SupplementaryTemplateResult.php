<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\SupplementaryAnnouncementTemplate;
use DateTimeInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SupplementaryTemplateResult',
    required: ['id', 'name', 'variants', 'languageCodes', 'active', 'createdAt', 'updatedAt'],
)]
final readonly class SupplementaryTemplateResult
{
    /**
     * @param list<array{languageCode:string,audioAssetId:string}> $variants
     * @param list<string>                                         $languageCodes
     */
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(example: 'Безопасность')]
        public string $name,
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
        public array $variants,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['ro-MD', 'ru'])]
        public array $languageCodes,
        public bool $active,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(SupplementaryAnnouncementTemplate $template): self
    {
        return new self(
            id: $template->getId()->toRfc4122(),
            name: $template->getName(),
            variants: $template->getVariants(),
            languageCodes: $template->getLanguageCodes(),
            active: $template->isActive(),
            createdAt: $template->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $template->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
