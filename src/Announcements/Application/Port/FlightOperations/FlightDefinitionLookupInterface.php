<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\FlightOperations;

use Symfony\Component\Uid\Uuid;

interface FlightDefinitionLookupInterface
{
    public function findById(Uuid $id): ?FlightDefinitionSnapshot;
}
