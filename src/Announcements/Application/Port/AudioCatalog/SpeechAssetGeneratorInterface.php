<?php

declare(strict_types=1);

namespace App\Announcements\Application\Port\AudioCatalog;

interface SpeechAssetGeneratorInterface
{
    /**
     * Generates (or reuses from cache) a speech asset for the given text in the
     * given language, using the default voice for that language. Voice selection
     * per announcement type is a future concern and not exposed here.
     *
     * @return string the generated audio asset ID
     */
    public function generate(string $text, string $languageCode): string;
}
