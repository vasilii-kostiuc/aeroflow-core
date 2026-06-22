<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAnnouncementLanguagesException extends DomainException
{
    public static function empty(): self
    {
        return new self('An announcement must contain at least one language.');
    }

    public static function duplicate(string $languageCode): self
    {
        return new self(sprintf('Announcement language "%s" is duplicated.', $languageCode));
    }
}
