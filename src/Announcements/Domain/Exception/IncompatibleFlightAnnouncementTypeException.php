<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class IncompatibleFlightAnnouncementTypeException extends DomainException
{
    public static function forDirection(string $type, string $direction): self
    {
        return new self(sprintf('Announcement type "%s" is not compatible with "%s" flights.', $type, $direction));
    }
}
