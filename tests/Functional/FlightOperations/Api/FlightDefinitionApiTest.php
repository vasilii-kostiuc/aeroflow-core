<?php

declare(strict_types=1);

namespace App\Tests\Functional\FlightOperations\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FlightDefinitionApiTest extends WebTestCase
{
    public function testEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/flight-definitions');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateReadUpdateListAndActivationFlow(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $numberSuffix = (string) random_int(100, 999);

        $this->jsonRequest($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'af'.$numberSuffix,
            'direction' => 'departure',
            'originAirportCode' => 'kiv',
            'destinationAirportCode' => 'fco',
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->jsonResponse($client)['data'];
        self::assertSame('AF'.$numberSuffix, $created['flightNumber']);
        self::assertSame('KIV', $created['originAirportCode']);
        self::assertTrue($created['active']);
        $id = $created['id'];

        $this->jsonRequest($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'AF'.$numberSuffix,
            'direction' => 'departure',
            'originAirportCode' => 'KIV',
            'destinationAirportCode' => 'FCO',
        ]);
        self::assertResponseStatusCodeSame(409);

        $client->request('GET', '/api/v1/flight-definitions/'.$id);
        self::assertResponseIsSuccessful();
        self::assertSame($id, $this->jsonResponse($client)['data']['id']);

        $this->jsonRequest($client, 'PUT', '/api/v1/flight-definitions/'.$id, [
            'flightNumber' => 'wz'.$numberSuffix,
            'direction' => 'arrival',
            'originAirportCode' => 'fco',
            'destinationAirportCode' => 'kiv',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('WZ'.$numberSuffix, $this->jsonResponse($client)['data']['flightNumber']);

        $this->jsonRequest($client, 'POST', '/api/v1/flight-definitions/'.$id.'/deactivate', []);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->jsonResponse($client)['data']['active']);

        $this->jsonRequest($client, 'POST', '/api/v1/flight-definitions/'.$id.'/deactivate', []);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->jsonResponse($client)['data']['active']);

        $client->request('GET', '/api/v1/flight-definitions?active=false&direction=arrival&search='.$numberSuffix.'&page=1&limit=1');
        self::assertResponseIsSuccessful();
        $list = $this->jsonResponse($client)['data'];
        self::assertCount(1, $list['items']);
        self::assertSame(1, $list['pagination']['page']);
        self::assertSame(1, $list['pagination']['limit']);
        self::assertSame(1, $list['pagination']['totalItems']);
        self::assertSame(1, $list['pagination']['totalPages']);

        $this->jsonRequest($client, 'POST', '/api/v1/flight-definitions/'.$id.'/activate', []);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse($client)['data']['active']);
    }

    public function testValidationAndNotFoundErrors(): void
    {
        $client = static::createClient();
        $this->authenticate($client);

        $this->jsonRequest($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'invalid',
            'direction' => 'departure',
            'originAirportCode' => 'KIV',
            'destinationAirportCode' => 'KIV',
        ]);
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions?page=0&limit=101');
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions?active[]=true');
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions?active=unknown');
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions?page=first');
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions?search[]=AF');
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions/not-a-uuid');
        self::assertResponseStatusCodeSame(422);

        $client->request('GET', '/api/v1/flight-definitions/01900000-0000-7000-8000-000000000001');
        self::assertResponseStatusCodeSame(404);
    }

    private function authenticate(KernelBrowser $client): void
    {
        $email = 'flight-definition-'.uniqid('', true).'@example.com';
        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => $email,
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);
        self::assertResponseStatusCodeSame(201);
        $token = $this->jsonResponse($client)['data']['accessToken'];
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
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
