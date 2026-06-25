<?php

declare(strict_types=1);

namespace App\Localization\Application;

interface LanguageProviderInterface
{
    /**
     * @return list<LanguageResult>
     */
    public function all(): array;
}
