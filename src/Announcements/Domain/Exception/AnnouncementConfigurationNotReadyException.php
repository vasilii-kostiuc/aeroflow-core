<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class AnnouncementConfigurationNotReadyException extends DomainException
{
    /** @param list<string> $errors */
    public static function withErrors(array $errors): self
    {
        return new self('Announcement configuration is not ready: '.implode(', ', $errors).'.');
    }
}
