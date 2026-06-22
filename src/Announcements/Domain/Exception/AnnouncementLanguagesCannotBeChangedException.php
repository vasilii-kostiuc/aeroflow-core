<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class AnnouncementLanguagesCannotBeChangedException extends DomainException
{
    public static function forCancelledAnnouncement(string $announcementId): self
    {
        return new self(sprintf(
            'Languages of cancelled announcement "%s" cannot be changed.',
            $announcementId,
        ));
    }
}
