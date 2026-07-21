<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Port\Tts;

use App\AudioCatalog\Domain\Exception\TextToSpeechUnavailableException;
use App\AudioCatalog\Domain\Exception\UnsupportedTtsVoiceException;

/**
 * Consumer-owned port to the TTS service. It describes Audio Catalog's need
 * ("turn this text into audio"), not the service's HTTP API. The port stays
 * neutral to the airport domain: only text, language and voice cross it.
 *
 * The implementation lives in Infrastructure/Integration and adapts the
 * aeroflow-tts HTTP contract. Application and domain never learn about HTTP.
 */
interface TextToSpeechPort
{
    /**
     * Resolves the voice that will speak for a language, and its current model
     * version, without synthesizing anything. Used to build the generation cache
     * key before deciding whether a call is needed. When $voice is null the
     * service's default voice for the language is chosen.
     *
     * @throws UnsupportedTtsVoiceException     when no voice matches the language/voice
     * @throws TextToSpeechUnavailableException when the service cannot be reached
     */
    public function describeVoice(string $languageCode, ?string $voice): TtsVoice;

    /**
     * Synthesizes speech for the given text with an already-resolved voice.
     *
     * @throws TextToSpeechUnavailableException when synthesis fails
     */
    public function synthesize(string $text, string $languageCode, string $voice): SynthesizedAudio;
}
