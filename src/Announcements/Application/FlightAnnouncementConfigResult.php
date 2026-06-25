<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FlightAnnouncementConfigResult',
    required: [
        'id',
        'flightDefinitionId',
        'announcementType',
        'enabled',
        'isValidForDispatcher',
        'validationErrors',
        'variants',
        'createdAt',
        'updatedAt',
    ],
)]
final readonly class FlightAnnouncementConfigResult
{
    /**
     * @param list<AnnouncementVariantResult> $variants
     * @param list<string>                    $validationErrors
     */
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(format: 'uuid')]
        public string $flightDefinitionId,
        #[OA\Property(enum: ['check_in_opening', 'check_in_continuation', 'check_in_closing', 'boarding_invitation', 'arrival'])]
        public string $announcementType,
        public bool $enabled,
        #[OA\Property(nullable: true, minimum: 1, maximum: 120)]
        public ?int $repeatEveryMinutes,
        public bool $isValidForDispatcher,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
        public array $validationErrors,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: AnnouncementVariantResult::class)))]
        public array $variants,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
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
