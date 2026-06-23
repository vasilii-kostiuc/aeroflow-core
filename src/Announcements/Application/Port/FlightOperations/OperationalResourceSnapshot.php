<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\FlightOperations;

final readonly class OperationalResourceSnapshot
{
    public function __construct(public string $id, public string $code)
    {
    }
}
