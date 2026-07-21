<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Infrastructure\Integration\Tts;

use App\AudioCatalog\Domain\Exception\TextToSpeechUnavailableException;
use App\AudioCatalog\Domain\Exception\UnsupportedTtsVoiceException;
use App\AudioCatalog\Infrastructure\Integration\Tts\HttpTextToSpeechAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HttpTextToSpeechAdapterTest extends TestCase
{
    private const VOICES = [
        ['voice' => 'dmitri', 'language' => 'ru', 'modelVersion' => 'v1'],
        ['voice' => 'lessac', 'language' => 'en', 'modelVersion' => 'v2'],
    ];

    public function testDescribeVoiceResolvesDefaultForLanguage(): void
    {
        $adapter = $this->adapter(fn (string $method, string $url): MockResponse => $this->route($method, $url));

        $voice = $adapter->describeVoice('ru', null);

        self::assertSame('dmitri', $voice->voice);
        self::assertSame('ru', $voice->languageCode);
        self::assertSame('v1', $voice->modelVersion);
    }

    public function testDescribeVoiceRejectsUnknownLanguage(): void
    {
        $adapter = $this->adapter(fn (string $method, string $url): MockResponse => $this->route($method, $url));

        $this->expectException(UnsupportedTtsVoiceException::class);
        $adapter->describeVoice('de', null);
    }

    public function testSynthesizeReturnsBytesAndMimeType(): void
    {
        $adapter = $this->adapter(function (string $method, string $url): MockResponse {
            if (str_contains($url, '/v1/synthesize')) {
                return new MockResponse('RIFF-binary-wav', ['response_headers' => ['content-type' => 'audio/wav']]);
            }

            return $this->route($method, $url);
        });

        $audio = $adapter->synthesize('привет', 'ru', 'dmitri');

        self::assertSame('RIFF-binary-wav', $audio->bytes);
        self::assertSame('audio/wav', $audio->mimeType);
    }

    public function testSynthesizeMapsNon2xxToUnavailable(): void
    {
        $adapter = $this->adapter(function (string $method, string $url): MockResponse {
            if (str_contains($url, '/v1/synthesize')) {
                return new MockResponse('boom', ['http_code' => 500]);
            }

            return $this->route($method, $url);
        });

        $this->expectException(TextToSpeechUnavailableException::class);
        $adapter->synthesize('привет', 'ru', 'dmitri');
    }

    /**
     * @param callable(string, string): MockResponse $router
     */
    private function adapter(callable $router): HttpTextToSpeechAdapter
    {
        $client = new MockHttpClient(static function (string $method, string $url) use ($router): ResponseInterface {
            return $router($method, $url);
        }, 'http://tts.test');

        return new HttpTextToSpeechAdapter($client, 'http://tts.test');
    }

    private function route(string $method, string $url): MockResponse
    {
        if (str_contains($url, '/v1/voices')) {
            return new MockResponse(json_encode(self::VOICES, JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        }

        return new MockResponse('', ['http_code' => 404]);
    }
}
