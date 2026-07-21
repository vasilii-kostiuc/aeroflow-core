<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Port\Tts;

/**
 * A resolved TTS voice: which voice will speak and the version of its underlying
 * model. The model version participates in the generation cache key so that a
 * model upgrade supersedes previously generated assets.
 */
final readonly class TtsVoice
{
    public function __construct(
        public string $voice,
        public string $languageCode,
        public string $modelVersion,
    ) {
    }
}
