<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;

final readonly class FlightAnnouncementConfigResult
{
    /**
     * @param list<AnnouncementVariantResult> $variants
     * @param list<string>                    $validationErrors
     */
    public function __construct(
        public string $id,
        public string $flightDefinitionId,
        public string $announcementType,
        public bool $enabled,
        public ?int $repeatEveryMinutes,
        public bool $isValidForDispatcher,
        public array $validationErrors,
        public array $variants,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(FlightAnnouncementConfig $config, bool $directionCompatible = true): self
    {
        $validationErrors = $config->validationErrors($directionCompatible);

        return new self(
            id: $config->getId()->toRfc4122(),
            flightDefinitionId: $config->getFlightDefinitionId()->toRfc4122(),
            announcementType: $config->getAnnouncementType()->value,
            enabled: $config->isEnabled(),
            repeatEveryMinutes: $config->getRepeatEveryMinutes(),
            isValidForDispatcher: [] === $validationErrors,
            validationErrors: $validationErrors,
            variants: array_map(
                static fn ($variant): AnnouncementVariantResult => AnnouncementVariantResult::fromEntity($variant),
                $config->getVariants(),
            ),
            createdAt: $config->getCreatedAt()->format(DATE_ATOM),
            updatedAt: $config->getUpdatedAt()->format(DATE_ATOM),
        );
    }
}
