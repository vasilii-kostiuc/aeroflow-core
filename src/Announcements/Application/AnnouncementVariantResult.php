<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\AnnouncementTemplateSegment;
use App\Announcements\Domain\Entity\AnnouncementVariant;

final readonly class AnnouncementVariantResult
{
    /** @param list<array<string, mixed>> $segments */
    public function __construct(
        public string $id,
        public string $languageCode,
        public int $sortOrder,
        public array $segments,
        public bool $enabled,
        public string $createdAt,
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
