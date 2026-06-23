<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidRepeatRuleException extends DomainException
{
    public static function forType(string $type): self
    {
        return new self(sprintf('Repeat rule is allowed only for "%s" announcements.', $type));
    }

    public static function invalidInterval(): self
    {
        return new self('Repeat interval must be between 1 and 120 minutes.');
    }
}
