<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\AudioCatalog\Application\Port\Tts\SynthesizedAudio;
use App\AudioCatalog\Application\Port\Tts\TextToSpeechPort;
use App\AudioCatalog\Application\Port\Tts\TtsVoice;
use App\AudioCatalog\Domain\Exception\UnsupportedTtsVoiceException;

/**
 * Deterministic in-process TTS for functional tests.
 *
 * Saving a variant with a `text` segment runs synthesis as a pre-step (task
 * 022), so without this stub the suite would only pass while a real
 * aeroflow-tts happened to be reachable. It honours the port's contract —
 * including the unsupported-language failure — but returns fixed silent audio
 * rather than speech.
 */
final readonly class StubTextToSpeech implements TextToSpeechPort
{
    /**
     * Languages this stub can speak, mirroring the voices aeroflow-tts ships.
     *
     * @var array<string, string>
     */
    private const VOICES = [
        'ru' => 'stub-ru',
        'en' => 'stub-en',
        'ro-MD' => 'stub-ro-md',
    ];

    private const MODEL_VERSION = 'stub-1.0';

    public function describeVoice(string $languageCode, ?string $voice): TtsVoice
    {
        $default = self::VOICES[$languageCode]
            ?? throw UnsupportedTtsVoiceException::forLanguage($languageCode);

        if (null !== $voice && $voice !== $default) {
            throw UnsupportedTtsVoiceException::forVoice($voice, $languageCode);
        }

        return new TtsVoice($default, $languageCode, self::MODEL_VERSION);
    }

    public function synthesize(string $text, string $languageCode, string $voice): SynthesizedAudio
    {
        return new SynthesizedAudio($this->silentWav(), 'audio/wav');
    }

    /**
     * A valid, empty PCM WAV: enough for the catalog to store and serve it.
     */
    private function silentWav(): string
    {
        return "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00"
            ."\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00";
    }
}
