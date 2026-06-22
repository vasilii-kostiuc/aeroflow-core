<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class AnnouncementNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Announcement "%s" was not found.', $id));
    }
}
