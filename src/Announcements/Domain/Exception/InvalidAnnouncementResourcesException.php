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

    public static function missingFlightDefinition(): self
    {
        return new self('Flight announcement requires a flight definition.');
    }

    public static function unexpectedFlightDefinition(): self
    {
        return new self('Supplementary announcement must not carry a flight definition.');
    }
}
