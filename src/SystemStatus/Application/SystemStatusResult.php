<?php

declare(strict_types=1);

namespace App\SystemStatus\Application;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SystemStatusResult', required: ['checkedAt', 'overall', 'components'])]
final readonly class SystemStatusResult
{
    /**
     * @param list<ComponentStatus> $components
     */
    public function __construct(
        #[OA\Property(format: 'date-time')]
        public string $checkedAt,
        /** The worst status among the components. */
        #[OA\Property(enum: ['ok', 'warning', 'down', 'unknown'])]
        public string $overall,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: ComponentStatus::class)))]
        public array $components,
    ) {
    }
}
