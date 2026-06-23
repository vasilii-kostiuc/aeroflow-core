<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAnnouncementVariantSourceException extends DomainException
{
    public static function missingAudioAsset(): self
    {
        return new self('Audio announcement variant requires a valid audio asset id.');
    }

    public static function missingText(): self
    {
        return new self('Text announcement variant requires non-empty text.');
    }

    public static function invalidSortOrder(): self
    {
        return new self('Announcement variant sort order must be greater than zero.');
    }
}
