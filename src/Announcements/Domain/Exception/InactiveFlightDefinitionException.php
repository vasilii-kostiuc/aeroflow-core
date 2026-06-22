<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InactiveFlightDefinitionException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Flight definition "%s" is inactive.', $id));
    }
}
