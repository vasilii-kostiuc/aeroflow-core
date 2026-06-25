<?php

declare(strict_types=1);

namespace App\Tests\Functional\Announcements\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AnnouncementApiTest extends WebTestCase
{
    public function testCreatesAnnouncementFromFlightOccurrenceAndUpdatesOccurrenceStatus(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createArrivalFlightDefinition($client);
        $occurrenceId = $this->createOccurrence($client, $flightId);
        $assetId = $this->uploadAsset($client);

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId), [
            'announcementType' => 'arrival',
            'enabled' => true,
            'repeatEveryMinutes' => null,
        ]);
        $configId = $this->response($client)['data']['id'];
        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'en',
            'sortOrder' => 1,
            'segments' => [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => $assetId]],
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->json($client, 'POST', '/api/v1/announcements', [
            'type' => 'arrival',
            'flightOccurrenceId' => $occurrenceId,
            'languages' => ['en'],
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->response($client)['data'];
        self::assertSame($occurrenceId, $created['flightOccurrenceId']);
        self::assertSame($flightId, $created['flightDefinitionId']);

        $client->request('GET', '/api/v1/flight-occurrences/'.$occurrenceId);
        self::assertResponseIsSuccessful();
        self::assertSame('arrival_announced', $this->response($client)['data']['status']);
    }

    public function testCreatesPreparedAnnouncementFromGateSlot(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client);
        $gateId = $this->createGate($client);
        $assetId = $this->uploadAsset($client);

        $this->json($client, 'POST', '/api/v1/admin/audio-prompts', [
            'kind' => 'gate_code',
            'value' => 'A12',
            'languageCode' => 'en',
            'audioAssetId' => $assetId,
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId), [
            'announcementType' => 'boarding_invitation',
            'enabled' => true,
            'repeatEveryMinutes' => null,
        ]);
        $configId = $this->response($client)['data']['id'];
        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'en',
            'sortOrder' => 1,
            'segments' => [['sortOrder' => 1, 'type' => 'dynamic_slot', 'slot' => 'gate_code']],
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->json($client, 'POST', '/api/v1/announcements', [
            'type' => 'boarding_invitation',
            'flightDefinitionId' => $flightId,
            'languages' => ['en'],
            'gateId' => $gateId,
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->response($client)['data'];
        self::assertSame('A12', $created['gate']['code']);
        self::assertSame($assetId, $created['audioSequence'][0]['items'][0]['audioAssetId']);
    }

    private function createGate(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/admin/gates', ['code' => 'A12', 'displayName' => 'Gate A12', 'sortOrder' => 1]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function uploadAsset(KernelBrowser $client): string
    {
        $path = tempnam(sys_get_temp_dir(), 'prompt-');
        self::assertIsString($path);
        file_put_contents($path, "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00");
        $client->request('POST', '/api/v1/admin/audio-assets', ['languageCode' => 'en'], ['file' => new UploadedFile($path, 'gate-a12.wav', 'audio/wav', null, true)]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createFlightDefinition(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'AN'.random_int(100, 999),
            'direction' => 'departure',
            'originAirportCode' => 'RMO',
            'destinationAirportCode' => 'FCO',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createArrivalFlightDefinition(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'AR'.random_int(100, 999),
            'direction' => 'arrival',
            'originAirportCode' => 'FCO',
            'destinationAirportCode' => 'RMO',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createOccurrence(KernelBrowser $client, string $flightId): string
    {
        $this->json($client, 'POST', '/api/v1/flight-occurrences', [
            'flightDefinitionId' => $flightId,
            'operationalDate' => '2026-06-25',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->json($client, 'POST', '/api/v1/register', [
            'email' => 'announcement-'.uniqid('', true).'@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);
        self::assertResponseStatusCodeSame(201);
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$this->response($client)['data']['accessToken']);
    }

    /** @param array<string,mixed> $payload */
    private function json(KernelBrowser $client, string $method, string $uri, array $payload): void
    {
        $client->request($method, $uri, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function response(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
