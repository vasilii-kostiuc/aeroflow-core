<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Port\Tts;

/**
 * Raw audio produced by the TTS service for one synthesis request. Carries only
 * bytes and their MIME type — the service is neutral to the airport domain and
 * returns nothing about announcements, flights or priorities.
 */
final readonly class SynthesizedAudio
{
    public function __construct(
        public string $bytes,
        public string $mimeType,
    ) {
    }
}
