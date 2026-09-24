<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class EmergencyTemplateLanguageNotConfiguredException extends DomainException
{
    public static function forLanguage(string $templateId, string $languageCode): self
    {
        return new self(sprintf('Language "%s" is not configured on emergency announcement template "%s".', $languageCode, $templateId));
    }
}
