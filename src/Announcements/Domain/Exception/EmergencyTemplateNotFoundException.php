<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class EmergencyTemplateNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Emergency announcement template "%s" was not found.', $id));
    }
}
