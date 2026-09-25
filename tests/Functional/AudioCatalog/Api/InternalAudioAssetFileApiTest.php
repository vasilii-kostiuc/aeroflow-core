<?php

declare(strict_types=1);

namespace App\Tests\Functional\AudioCatalog\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Download contract for aeroflow-agent (task 016): the endpoint is outside /api/v1
 * and needs no user JWT — the agent fetches by asset id only.
 */
final class InternalAudioAssetFileApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testStreamsActiveAssetFileWithoutAuthentication(): void
    {
        $client = static::createClient();
        $assetId = $this->uploadAsset($client);

        // A fresh browser without the Authorization header models the agent.
        $client->setServerParameter('HTTP_AUTHORIZATION', '');
        $client->request('GET', sprintf('/internal/v1/audio-assets/%s/file', $assetId));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'audio/x-wav');
        $body = (string) $client->getInternalResponse()->getContent();
        self::assertStringStartsWith('RIFF', $body);
    }

    public function testUnknownAssetGives404(): void
    {
        $client = static::createClient();

        $client->request('GET', sprintf('/internal/v1/audio-assets/%s/file', Uuid::v7()->toRfc4122()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testMalformedIdGives404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/internal/v1/audio-assets/not-a-uuid/file');

        self::assertResponseStatusCodeSame(404);
    }

    private function uploadAsset(KernelBrowser $client): string
    {
        $this->authenticate($client);

        $path = tempnam(sys_get_temp_dir(), 'audio-asset-');
        self::assertIsString($path);
        file_put_contents(
            $path,
            "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00",
        );

        $client->request(
            'POST',
            '/api/v1/admin/audio-assets',
            ['languageCode' => 'en'],
            ['file' => new UploadedFile($path, 'agent-download.wav', 'audio/wav', null, true)],
        );
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->actAs($client);
    }

    /**
     * @return array<string, mixed>
     */
    private function response(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
