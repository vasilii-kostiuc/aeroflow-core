<?php

declare(strict_types=1);

namespace App\AudioCatalog\Infrastructure\Integration\Tts;

use App\AudioCatalog\Application\Port\Tts\SynthesizedAudio;
use App\AudioCatalog\Application\Port\Tts\TextToSpeechPort;
use App\AudioCatalog\Application\Port\Tts\TtsVoice;
use App\AudioCatalog\Domain\Exception\TextToSpeechUnavailableException;
use App\AudioCatalog\Domain\Exception\UnsupportedTtsVoiceException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adapts the aeroflow-tts HTTP contract to {@see TextToSpeechPort}. This is the
 * only place in Core that knows the service speaks HTTP; the application and
 * domain layers see just the port. Any transport or non-2xx failure becomes a
 * {@see TextToSpeechUnavailableException} so no partial asset is ever created.
 */
final readonly class HttpTextToSpeechAdapter implements TextToSpeechPort
{
    public function __construct(
        private HttpClientInterface $client,
        private string $baseUrl,
        private float $timeoutSeconds = 30.0,
    ) {
    }

    public function describeVoice(string $languageCode, ?string $voice): TtsVoice
    {
        $voices = $this->fetchVoices();

        $match = null;
        foreach ($voices as $candidate) {
            if (($candidate['language'] ?? null) !== $languageCode) {
                continue;
            }
            if (null !== $voice) {
                if (($candidate['voice'] ?? null) === $voice) {
                    $match = $candidate;
                    break;
                }
                continue;
            }
            // Default voice for a language: first one the service lists.
            $match = $candidate;
            break;
        }

        if (null === $match) {
            throw null !== $voice ? UnsupportedTtsVoiceException::forVoice($voice, $languageCode) : UnsupportedTtsVoiceException::forLanguage($languageCode);
        }

        return new TtsVoice(
            voice: (string) $match['voice'],
            languageCode: (string) $match['language'],
            modelVersion: (string) ($match['modelVersion'] ?? 'unknown'),
        );
    }

    public function synthesize(string $text, string $languageCode, string $voice): SynthesizedAudio
    {
        try {
            $response = $this->client->request('POST', $this->url('/v1/synthesize'), [
                'json' => ['text' => $text, 'language' => $languageCode, 'voice' => $voice],
                'timeout' => $this->timeoutSeconds,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                throw TextToSpeechUnavailableException::synthesisFailed(sprintf('service returned HTTP %d: %s', $status, $this->safeBody($response)));
            }

            $bytes = $response->getContent();
            $mimeType = $this->contentType($response);
        } catch (HttpClientExceptionInterface $exception) {
            throw TextToSpeechUnavailableException::synthesisFailed($exception->getMessage(), $exception);
        }

        if ('' === $bytes) {
            throw TextToSpeechUnavailableException::synthesisFailed('service returned empty audio');
        }

        return new SynthesizedAudio($bytes, $mimeType);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchVoices(): array
    {
        try {
            $response = $this->client->request('GET', $this->url('/v1/voices'), [
                'timeout' => $this->timeoutSeconds,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                throw TextToSpeechUnavailableException::synthesisFailed(sprintf('voices lookup returned HTTP %d', $status));
            }

            /** @var list<array<string, mixed>> $voices */
            $voices = $response->toArray();
        } catch (HttpClientExceptionInterface $exception) {
            throw TextToSpeechUnavailableException::synthesisFailed($exception->getMessage(), $exception);
        }

        return $voices;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').$path;
    }

    private function contentType(object $response): string
    {
        if (!method_exists($response, 'getHeaders')) {
            return 'audio/wav';
        }
        $headers = $response->getHeaders(false);
        $contentType = $headers['content-type'][0] ?? 'audio/wav';

        // Strip charset/boundary parameters: "audio/wav; charset=binary" -> "audio/wav".
        $semicolon = strpos($contentType, ';');

        return false === $semicolon ? $contentType : trim(substr($contentType, 0, $semicolon));
    }

    private function safeBody(object $response): string
    {
        if (!method_exists($response, 'getContent')) {
            return '';
        }

        try {
            return mb_substr($response->getContent(false), 0, 500);
        } catch (HttpClientExceptionInterface) {
            return '';
        }
    }
}
