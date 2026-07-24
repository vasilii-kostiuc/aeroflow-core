<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InactiveSupplementaryTemplateException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Supplementary announcement template "%s" is inactive and cannot be launched.', $id));
    }
}
