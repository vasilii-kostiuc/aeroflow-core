<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class AudioAssetUnavailableException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Active audio asset "%s" was not found.', $id));
    }
}
