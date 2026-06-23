<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAnnouncementResourcesException extends DomainException
{
    public static function missingCheckInCounters(): self
    {
        return new self('Check-in announcement requires at least one check-in counter.');
    }

    public static function missingGate(): self
    {
        return new self('Boarding announcement requires a gate.');
    }
}
