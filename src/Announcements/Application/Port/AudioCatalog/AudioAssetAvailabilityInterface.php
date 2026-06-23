<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\AudioCatalog;

use Symfony\Component\Uid\Uuid;

interface AudioAssetAvailabilityInterface
{
    public function isAvailable(Uuid $id): bool;
}
