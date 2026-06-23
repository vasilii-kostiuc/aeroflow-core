<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class OperationalResourceUnavailableException extends DomainException
{
    /** @param list<string> $ids */
    public static function counters(array $ids): self
    {
        return new self(sprintf('Some check-in counters are unknown, inactive or duplicated: %s.', implode(', ', $ids)));
    }

    public static function gate(string $id): self
    {
        return new self(sprintf('Gate "%s" is unknown or inactive.', $id));
    }
}
