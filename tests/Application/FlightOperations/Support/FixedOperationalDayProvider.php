<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations\Support;

use App\FlightOperations\Application\OperationalDay\OperationalDayProviderInterface;
use DateTimeImmutable;

final readonly class FixedOperationalDayProvider implements OperationalDayProviderInterface
{
    public function __construct(private string $date)
    {
    }

    public function today(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->date);
    }
}
