<?php

declare(strict_types=1);

namespace App\Tests\Functional\FlightOperations\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FlightOccurrenceApiTest extends WebTestCase
{
    public function testCreateReadAndListFlow(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client);

        $this->json($client, 'POST', '/api/v1/flight-occurrences', [
            'flightDefinitionId' => $flightId,
            'operationalDate' => '2026-06-25',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->response($client)['data'];
        self::assertSame($flightId, $created['flightDefinitionId']);
        self::assertSame('scheduled', $created['status']);
        self::assertSame('manual', $created['source']);
        $id = $created['id'];

        $client->request('GET', '/api/v1/flight-occurrences/'.$id);
        self::assertResponseIsSuccessful();
        self::assertSame($id, $this->response($client)['data']['id']);

        $client->request('GET', '/api/v1/flight-occurrences?operationalDate=2026-06-25&status=scheduled&source=manual');
        self::assertResponseIsSuccessful();
        $list = $this->response($client)['data'];
        self::assertGreaterThanOrEqual(1, $list['pagination']['totalItems']);

        $this->json($client, 'POST', '/api/v1/flight-occurrences', [
            'flightDefinitionId' => $flightId,
            'operationalDate' => '2026-06-25',
        ]);
        self::assertResponseStatusCodeSame(409);
    }

    public function testEnsureManualIsIdempotent(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client);

        $this->json($client, 'POST', '/api/v1/flight-occurrences:ensure-manual', [
            'flightDefinitionId' => $flightId,
            'operationalDate' => '2026-06-25',
        ]);
        self::assertResponseIsSuccessful();
        $first = $this->response($client)['data'];
        self::assertSame('scheduled', $first['status']);
        self::assertSame('manual', $first['source']);

        $this->json($client, 'POST', '/api/v1/flight-occurrences:ensure-manual', [
            'flightDefinitionId' => $flightId,
            'operationalDate' => '2026-06-25',
        ]);
        self::assertResponseIsSuccessful();
        $second = $this->response($client)['data'];

        self::assertSame($first['id'], $second['id']);
    }

    private function createFlightDefinition(KernelBrowser $client): string
    {
        $this->json($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'OC'.random_int(100, 999),
            'direction' => 'departure',
            'originAirportCode' => 'RMO',
            'destinationAirportCode' => 'FCO',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->response($client)['data']['id'];
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->json($client, 'POST', '/api/v1/register', [
            'email' => 'occurrence-'.uniqid('', true).'@example.com',
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
