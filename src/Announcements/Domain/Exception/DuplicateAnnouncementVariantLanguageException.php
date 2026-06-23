<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class DuplicateAnnouncementVariantLanguageException extends DomainException
{
    public static function forLanguage(string $languageCode): self
    {
        return new self(sprintf('Announcement variant for language "%s" already exists.', $languageCode));
    }
}
