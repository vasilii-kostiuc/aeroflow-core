<?php

declare(strict_types=1);

namespace App\Tests\Functional\Announcements\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AnnouncementApiTest extends WebTestCase
{
    public function testCreateReadListChangeLanguagesAndCancel(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightDefinitionId = $this->createFlightDefinition($client);

        $this->json($client, 'POST', '/api/v1/announcements', [
            'type' => 'boarding_invitation',
            'flightDefinitionId' => $flightDefinitionId,
            'languages' => ['ro', 'ru', 'en'],
            'gateCode' => 'a12',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->response($client)['data'];
        self::assertSame($flightDefinitionId, $created['flightDefinitionId']);
        self::assertSame(['ro', 'ru', 'en'], $created['languages']);
        $id = $created['id'];

        $client->request('GET', '/api/v1/announcements/'.$id);
        self::assertResponseIsSuccessful();

        $this->json($client, 'PUT', '/api/v1/announcements/'.$id.'/languages', [
            'languages' => ['en', 'ro'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(['en', 'ro'], $this->response($client)['data']['languages']);

        $this->json($client, 'POST', '/api/v1/announcements/'.$id.'/cancel', []);
        self::assertResponseIsSuccessful();
        self::assertSame('cancelled', $this->response($client)['data']['status']);

        $client->request('GET', '/api/v1/announcements');
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->response($client)['data']);
    }

    private function createFlightDefinition(KernelBrowser $client): string
    {
        $suffix = (string) random_int(100, 999);
        $this->json($client, 'POST', '/api/v1/flight-definitions', [
            'flightNumber' => 'AN'.$suffix,
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
            'email' => 'announcement-'.uniqid('', true).'@example.com',
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
