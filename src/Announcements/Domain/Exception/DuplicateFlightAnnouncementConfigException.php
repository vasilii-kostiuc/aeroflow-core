<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateFlightAnnouncementConfigException extends DomainException
{
    public static function forFlightAndType(string $flightDefinitionId, string $type): self
    {
        return new self(sprintf(
            'Announcement config "%s" already exists for flight definition "%s".',
            $type,
            $flightDefinitionId,
        ));
    }
}
