<?php

declare(strict_types=1);

namespace App\Tests\Functional\Announcements\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FlightAnnouncementConfigApiTest extends WebTestCase
{
    public function testAdminCanConfigureTextAndAudioVariants(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client, 'departure');

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId), [
            'announcementType' => 'check_in_opening',
            'enabled' => true,
            'repeatEveryMinutes' => null,
        ]);
        self::assertResponseStatusCodeSame(201);
        $config = $this->response($client)['data'];
        self::assertFalse($config['isValidForDispatcher']);
        self::assertSame(['no_active_variants'], $config['validationErrors']);
        $configId = $config['id'];

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'ro-MD',
            'sortOrder' => 1,
            'segments' => [['sortOrder' => 1, 'type' => 'text', 'text' => 'Înregistrarea este deschisă.']],
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(201);
        $config = $this->response($client)['data'];
        self::assertFalse($config['isValidForDispatcher']);
        self::assertContains('text_segment_requires_tts', $config['validationErrors']);
        $variantId = $config['variants'][0]['id'];

        $this->json($client, 'PATCH', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants/%s', $flightId, $configId, $variantId), [
            'languageCode' => 'ro-MD',
            'sortOrder' => 2,
            'segments' => [['sortOrder' => 1, 'type' => 'text', 'text' => 'Text actualizat.']],
            'enabled' => false,
        ]);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->response($client)['data']['isValidForDispatcher']);

        $audioPath = $this->createWavFile();
        $client->request(
            'POST',
            '/api/v1/admin/audio-assets',
            ['languageCode' => 'en'],
            ['file' => new UploadedFile($audioPath, 'begin-en.wav', 'audio/wav', null, true)],
        );
        self::assertResponseStatusCodeSame(201);
        $asset = $this->response($client)['data'];
        self::assertSame('audio/x-wav', $asset['mimeType']);
        self::assertGreaterThan(0, $asset['sizeBytes']);

        $client->request('GET', '/api/v1/admin/audio-assets');
        self::assertResponseIsSuccessful();
        self::assertSame($asset['id'], $this->response($client)['data'][0]['id']);

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'en',
            'sortOrder' => 1,
            'segments' => [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => $asset['id']]],
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(201);
        self::assertTrue($this->response($client)['data']['isValidForDispatcher']);

        $client->request('GET', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->response($client)['data']);
    }

    public function testRejectsIncompatibleTypeAndUnknownAudioAsset(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client, 'arrival');

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId), [
            'announcementType' => 'boarding_invitation',
            'enabled' => true,
            'repeatEveryMinutes' => null,
        ]);
        self::assertResponseStatusCodeSame(422);

        $invalidPath = tempnam(sys_get_temp_dir(), 'invalid-audio-');
        self::assertIsString($invalidPath);
        file_put_contents($invalidPath, 'not audio');
        $client->request(
            'POST',
            '/api/v1/admin/audio-assets',
            ['languageCode' => 'en'],
            ['file' => new UploadedFile($invalidPath, 'invalid.wav', 'audio/wav', null, true)],
        );
        self::assertResponseStatusCodeSame(422);

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId), [
            'announcementType' => 'arrival',
            'enabled' => true,
            'repeatEveryMinutes' => null,
        ]);
        $configId = $this->response($client)['data']['id'];

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'en',
            'sortOrder' => 1,
            'segments' => [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => '01900000-0000-7000-8000-000000000099']],
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    private function createWavFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'audio-asset-');
        self::assertIsString($path);
        file_put_contents(
            $path,
            "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00",
        );

        return $path;
    }

    private function createFlightDefinition(KernelBrowser $client, string $direction): string
    {
        $this->json($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'FC'.random_int(100, 999),
            'direction' => $direction,
            'originAirportCode' => 'RMO',
            'destinationAirportCode' => 'FCO',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->json($client, 'POST', '/api/v1/register', [
            'email' => 'flight-config-'.uniqid('', true).'@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);
        self::assertResponseStatusCodeSame(201);
        $client->setServerParameter(
            'HTTP_AUTHORIZATION',
            'Bearer '.$this->response($client)['data']['accessToken'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(KernelBrowser $client, string $method, string $uri, array $payload): void
    {
        $client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function response(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
