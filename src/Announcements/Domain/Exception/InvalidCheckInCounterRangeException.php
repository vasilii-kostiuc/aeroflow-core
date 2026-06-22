<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidCheckInCounterRangeException extends DomainException
{
    public static function forBounds(int $start, int $end): self
    {
        return new self(sprintf('Invalid check-in counter range "%d-%d".', $start, $end));
    }
}
