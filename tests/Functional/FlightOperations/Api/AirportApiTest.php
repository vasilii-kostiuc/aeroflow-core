<?php

declare(strict_types=1);

namespace App\Tests\Functional\FlightOperations\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AirportApiTest extends WebTestCase
{
    public function testCreateListUpdateAndStatusFlow(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $code = 'Z'.chr(random_int(65, 90)).chr(random_int(65, 90));

        $this->jsonRequest($client, 'POST', '/api/v1/airports', [
            'code' => $code,
            'name' => 'Test International Airport',
            'cityName' => 'Test City',
            'countryCode' => 'MD',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->jsonResponse($client)['data'];
        self::assertSame($code, $created['code']);
        $id = $created['id'];

        $client->request('GET', '/api/v1/airports?search='.$code.'&active=true&page=1&limit=20');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->jsonResponse($client)['data']['items']);

        $this->jsonRequest($client, 'PUT', '/api/v1/airports/'.$id, [
            'name' => 'Updated Airport',
            'cityName' => 'Updated City',
            'countryCode' => 'RO',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('Updated City', $this->jsonResponse($client)['data']['cityName']);

        $this->jsonRequest($client, 'POST', '/api/v1/airports/'.$id.'/deactivate', []);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->jsonResponse($client)['data']['active']);

        $this->jsonRequest($client, 'POST', '/api/v1/airports/'.$id.'/activate', []);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse($client)['data']['active']);
    }

    private function authenticate(KernelBrowser $client): void
    {
        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => 'airport-'.uniqid('', true).'@example.com',
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
