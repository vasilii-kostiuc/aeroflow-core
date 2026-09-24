<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidEmergencyTemplateException extends DomainException
{
    public static function emptyName(): self
    {
        return new self('Emergency announcement template name must not be empty.');
    }

    public static function noVariants(): self
    {
        return new self('Emergency announcement template requires at least one language variant.');
    }

    public static function duplicateLanguage(string $languageCode): self
    {
        return new self(sprintf('Duplicate language variant "%s" in emergency announcement template.', $languageCode));
    }

    public static function invalidAsset(string $audioAssetId): self
    {
        return new self(sprintf('Emergency announcement template variant references an invalid audio asset id "%s".', $audioAssetId));
    }
}
