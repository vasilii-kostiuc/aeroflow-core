<?php

declare(strict_types=1);

namespace App\Announcements\Application\GetEmergencyMode;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EmergencyModeResult', required: ['active', 'since'])]
final readonly class EmergencyModeResult
{
    public function __construct(
        public bool $active,
        #[OA\Property(format: 'date-time', nullable: true)]
        public ?string $since,
    ) {
    }
}
