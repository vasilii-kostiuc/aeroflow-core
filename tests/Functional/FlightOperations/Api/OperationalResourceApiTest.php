<?php

declare(strict_types=1);

namespace App\Tests\Functional\FlightOperations\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OperationalResourceApiTest extends WebTestCase
{
    public function testGateAndCheckInCounterHaveIndependentFlows(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $code = 'T'.strtoupper(bin2hex(random_bytes(5)));

        $gate = $this->createResource($client, '/api/v1/admin/gates', $code, 'Gate '.$code);
        $counter = $this->createResource(
            $client,
            '/api/v1/admin/check-in-counters',
            $code,
            'Check-in counter '.$code,
        );

        self::assertNotSame($gate['id'], $counter['id']);
        self::assertSame($code, $gate['code']);
        self::assertSame($code, $counter['code']);

        $this->jsonRequest($client, 'PATCH', '/api/v1/admin/gates/'.$gate['id'], [
            'code' => $code,
            'displayName' => 'Updated gate',
            'sortOrder' => 2,
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('Updated gate', $this->jsonResponse($client)['data']['displayName']);

        $this->jsonRequest($client, 'POST', '/api/v1/admin/check-in-counters/'.$counter['id'].'/deactivate', []);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->jsonResponse($client)['data']['active']);

        $client->request('GET', '/api/v1/admin/check-in-counters?active=false');
        self::assertResponseIsSuccessful();
        $inactiveCounters = $this->jsonResponse($client)['data'];
        self::assertContains($counter['id'], array_column($inactiveCounters, 'id'));

        $client->request('GET', '/api/v1/admin/gates?active=true');
        self::assertResponseIsSuccessful();
        $activeGates = $this->jsonResponse($client)['data'];
        self::assertContains($gate['id'], array_column($activeGates, 'id'));
    }

    /**
     * @return array<string, mixed>
     */
    private function createResource(
        KernelBrowser $client,
        string $uri,
        string $code,
        string $displayName,
    ): array {
        $this->jsonRequest($client, 'POST', $uri, [
            'code' => $code,
            'displayName' => $displayName,
            'sortOrder' => 1,
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->jsonResponse($client)['data'];
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => 'operational-resource-'.uniqid('', true).'@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);
        self::assertResponseStatusCodeSame(201);
        $client->setServerParameter(
            'HTTP_AUTHORIZATION',
            'Bearer '.$this->jsonResponse($client)['data']['accessToken'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(KernelBrowser $client, string $method, string $uri, array $payload): void
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
    private function jsonResponse(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
