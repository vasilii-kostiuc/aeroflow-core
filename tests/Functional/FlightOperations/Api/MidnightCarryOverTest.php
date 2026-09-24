<?php

declare(strict_types=1);

namespace App\Tests\Functional\FlightOperations\Api;

use App\Tests\Support\FrozenOperationalDayProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A flight whose check-in opens before midnight is finished after it (task 027):
 * the run stays on the dispatcher board across the day change, keeps its own
 * operational date, blocks a second live run, and once boarded frees the card for
 * a run of the new day.
 */
final class MidnightCarryOverTest extends WebTestCase
{
    private const BEFORE_MIDNIGHT = '2031-03-14';
    private const AFTER_MIDNIGHT = '2031-03-15';

    protected function tearDown(): void
    {
        FrozenOperationalDayProvider::release();
        parent::tearDown();
    }

    public function testRunStartedBeforeMidnightIsFinishedAfterIt(): void
    {
        FrozenOperationalDayProvider::freeze(self::BEFORE_MIDNIGHT);
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client);
        $counterId = $this->createCounter($client);
        $gateId = $this->createGate($client);
        $this->configureAnnouncements($client, $flightId);

        // 23:30 — the dispatcher opens check-in.
        $this->json($client, 'POST', '/api/v1/flight-occurrences:ensure-manual', ['flightDefinitionId' => $flightId]);
        self::assertResponseIsSuccessful();
        $run = $this->response($client)['data'];
        self::assertSame(self::BEFORE_MIDNIGHT, $run['operationalDate']);

        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/check-in:open', $run['id']), [
            'languages' => ['en'],
            'checkInCounterIds' => [$counterId],
        ]);
        self::assertResponseIsSuccessful();

        $board = $this->board($client);
        self::assertSame(self::BEFORE_MIDNIGHT, $board['operationalDate']);
        $this->assertCardRun($board, $flightId, $run['id'], self::BEFORE_MIDNIGHT, 'check_in_open');

        // Midnight passes.
        FrozenOperationalDayProvider::freeze(self::AFTER_MIDNIGHT);

        $board = $this->board($client);
        self::assertSame(self::AFTER_MIDNIGHT, $board['operationalDate']);
        $this->assertCardRun($board, $flightId, $run['id'], self::BEFORE_MIDNIGHT, 'check_in_open');

        // The card cannot start a second live run while this one is unfinished.
        $this->json($client, 'POST', '/api/v1/flight-occurrences:ensure-manual', ['flightDefinitionId' => $flightId]);
        self::assertResponseStatusCodeSame(409);

        // 00:10 — check-in is closed on the carried-over run.
        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/check-in:close', $run['id']), [
            'languages' => ['en'],
        ]);
        self::assertResponseIsSuccessful();
        $this->assertCardRun($this->board($client), $flightId, $run['id'], self::BEFORE_MIDNIGHT, 'check_in_closed');

        // 00:20 — boarding finishes the run; it leaves the board.
        $this->json($client, 'POST', sprintf('/api/v1/flight-occurrences/%s/boarding', $run['id']), [
            'languages' => ['en'],
            'gateId' => $gateId,
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('boarding', $this->response($client)['data']['occurrence']['status']);
        self::assertSame([], $this->cardRows($this->board($client), $flightId));

        // The card is free: the next run belongs to the new operational day.
        $this->json($client, 'POST', '/api/v1/flight-occurrences:ensure-manual', ['flightDefinitionId' => $flightId]);
        self::assertResponseIsSuccessful();
        $nextRun = $this->response($client)['data'];
        self::assertNotSame($run['id'], $nextRun['id']);
        self::assertSame(self::AFTER_MIDNIGHT, $nextRun['operationalDate']);
        self::assertSame(1, $nextRun['sequenceNumber']);
        $this->assertCardRun($this->board($client), $flightId, $nextRun['id'], self::AFTER_MIDNIGHT, 'scheduled');
    }

    /** @return array<string,mixed> */
    private function board(KernelBrowser $client): array
    {
        $client->request('GET', '/api/v1/dispatcher/board');
        self::assertResponseIsSuccessful();

        return $this->response($client)['data'];
    }

    /**
     * @param array<string,mixed> $board
     *
     * @return list<array<string,mixed>>
     */
    private function cardRows(array $board, string $flightId): array
    {
        return array_values(array_filter(
            $board['occurrences'],
            static fn (array $row): bool => $row['flightDefinitionId'] === $flightId,
        ));
    }

    /** @param array<string,mixed> $board */
    private function assertCardRun(array $board, string $flightId, string $runId, string $operationalDate, string $status): void
    {
        $rows = $this->cardRows($board, $flightId);
        self::assertCount(1, $rows);
        self::assertSame($runId, $rows[0]['id']);
        self::assertSame($operationalDate, $rows[0]['operationalDate']);
        self::assertSame($status, $rows[0]['status']);
    }

    private function configureAnnouncements(KernelBrowser $client, string $flightId): void
    {
        $assetId = $this->uploadAsset($client);
        $segments = [['sortOrder' => 1, 'type' => 'audio_asset', 'audioAssetId' => $assetId]];

        foreach (['check_in_opening', 'check_in_closing', 'boarding_invitation'] as $type) {
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
    }

    private function uploadAsset(KernelBrowser $client): string
    {
        $path = tempnam(sys_get_temp_dir(), 'midnight-');
        self::assertIsString($path);
        file_put_contents($path, "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00");
        $client->request('POST', '/api/v1/admin/audio-assets', ['languageCode' => 'en'], ['file' => new UploadedFile($path, 'midnight.wav', 'audio/wav', null, true)]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createFlightDefinition(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'MN'.random_int(100, 999),
            'direction' => 'departure',
            'originAirportCode' => 'RMO',
            'destinationAirportCode' => 'FCO',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createCounter(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/admin/check-in-counters', ['code' => 'M'.random_int(1, 9999), 'displayName' => 'Counter', 'sortOrder' => 1]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function createGate(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/admin/gates', ['code' => 'M'.random_int(1, 9999), 'displayName' => 'Gate', 'sortOrder' => 1]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->json($client, 'POST', '/api/v1/register', [
            'email' => 'midnight-'.uniqid('', true).'@example.com',
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
