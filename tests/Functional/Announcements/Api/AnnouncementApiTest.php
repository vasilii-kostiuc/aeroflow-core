<?php

declare(strict_types=1);

namespace App\Tests\Functional\Announcements\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AnnouncementApiTest extends WebTestCase
{
    public function testArrivalActionAdvancesOccurrenceAndCreatesAnnouncement(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createArrivalFlightDefinition($client);
        $occurrenceId = $this->createOccurrence($client, $flightId);
        $assetId = $this->uploadAsset($client);
        $this->createConfigWithVariant($client, $flightId, 'arrival', [
            ['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => $assetId],
        ]);

        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/arrival', $occurrenceId), [
            'languages' => ['en'],
        ]);
        self::assertResponseIsSuccessful();
        $data = $this->response($client)['data'];
        self::assertSame('arrival_announced', $data['occurrence']['status']);
        self::assertNotEmpty($data['announcementId']);

        $client->request('GET', '/api/v1/announcements/'.$data['announcementId']);
        self::assertResponseIsSuccessful();
        $announcement = $this->response($client)['data'];
        self::assertSame($occurrenceId, $announcement['flightOccurrenceId']);
        self::assertSame($flightId, $announcement['flightDefinitionId']);
    }

    public function testBoardingActionResolvesGateSlotThroughOccurrenceLifecycle(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client);
        $occurrenceId = $this->createOccurrence($client, $flightId);
        $gateId = $this->createGate($client);
        $counterId = $this->createCounter($client);
        $assetId = $this->uploadAsset($client);

        $this->json($client, 'POST', '/api/v1/admin/audio-prompts', [
            'kind' => 'gate_code',
            'value' => 'A12',
            'languageCode' => 'en',
            'audioAssetId' => $assetId,
        ]);
        self::assertResponseStatusCodeSame(201);

        $assetSegment = [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => $assetId]];
        $this->createConfigWithVariant($client, $flightId, 'check_in_opening', $assetSegment);
        $this->createConfigWithVariant($client, $flightId, 'check_in_closing', $assetSegment);
        $this->createConfigWithVariant($client, $flightId, 'boarding_invitation', [
            ['sortOrder' => 1, 'type' => 'dynamic_slot', 'slot' => 'gate_code'],
        ]);

        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/check-in:open', $occurrenceId), [
            'languages' => ['en'],
            'checkInCounterIds' => [$counterId],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('check_in_open', $this->response($client)['data']['occurrence']['status']);

        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/check-in:close', $occurrenceId), [
            'languages' => ['en'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('check_in_closed', $this->response($client)['data']['occurrence']['status']);

        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/boarding', $occurrenceId), [
            'languages' => ['en'],
            'gateId' => $gateId,
        ]);
        self::assertResponseIsSuccessful();
        $data = $this->response($client)['data'];
        self::assertSame('boarding', $data['occurrence']['status']);

        $client->request('GET', '/api/v1/announcements/'.$data['announcementId']);
        self::assertResponseIsSuccessful();
        $announcement = $this->response($client)['data'];
        self::assertSame('A12', $announcement['gate']['code']);
        self::assertSame($assetId, $announcement['audioSequence'][0]['items'][0]['audioAssetId']);
    }

    public function testInvalidTransitionIsRejectedAtomically(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client);
        $occurrenceId = $this->createOccurrence($client, $flightId);
        $gateId = $this->createGate($client);
        $assetId = $this->uploadAsset($client);

        $this->json($client, 'POST', '/api/v1/admin/audio-prompts', [
            'kind' => 'gate_code',
            'value' => 'A12',
            'languageCode' => 'en',
            'audioAssetId' => $assetId,
        ]);
        self::assertResponseStatusCodeSame(201);
        $this->createConfigWithVariant($client, $flightId, 'boarding_invitation', [
            ['sortOrder' => 1, 'type' => 'dynamic_slot', 'slot' => 'gate_code'],
        ]);

        // Boarding requires check_in_closed; the occurrence is still scheduled.
        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/boarding', $occurrenceId), [
            'languages' => ['en'],
            'gateId' => $gateId,
        ]);
        self::assertResponseStatusCodeSame(409);

        // Transition rolled back: the occurrence is untouched and has no announcement.
        $client->request('GET', '/api/v1/flight-occurrences/'.$occurrenceId);
        self::assertResponseIsSuccessful();
        $occurrence = $this->response($client)['data'];
        self::assertSame('scheduled', $occurrence['status']);
        self::assertNull($occurrence['lastAnnouncementId']);
    }

    public function testPreconditionFailureWhenConfigMissing(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createArrivalFlightDefinition($client);
        $occurrenceId = $this->createOccurrence($client, $flightId);

        // No announcement config exists for the arrival type.
        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/arrival', $occurrenceId), [
            'languages' => ['en'],
        ]);
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-occurrences/'.$occurrenceId);
        self::assertResponseIsSuccessful();
        self::assertSame('scheduled', $this->response($client)['data']['status']);
    }

    /** @param list<array<string,mixed>> $segments */
    private function createConfigWithVariant(KernelBrowser $client, string $flightId, string $type, array $segments): void
    {
        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs', $flightId), [
            'announcementType' => $type,
            'enabled' => true,
            'repeatEveryMinutes' => null,
        ]);
        self::assertResponseStatusCodeSame(201);
        $configId = $this->response($client)['data']['id'];

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'en',
            'sortOrder' => 1,
            'segments' => $segments,
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(201);
    }

    private function createGate(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/admin/gates', ['code' => 'A12', 'displayName' => 'Gate A12', 'sortOrder' => 1]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createCounter(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/admin/check-in-counters', ['code' => 'C'.random_int(1, 999), 'displayName' => 'Counter', 'sortOrder' => 1]);
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
