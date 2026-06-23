<?php

declare(strict_types=1);

namespace App\Announcements\Application\DeleteAnnouncementVariant;

final readonly class DeleteAnnouncementVariantCommand
{
    public function __construct(
        public string $flightDefinitionId,
        public string $configId,
        public string $variantId,
    ) {
    }
}
