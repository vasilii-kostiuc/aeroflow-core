<?php

declare(strict_types=1);

namespace App\Tests\Functional\Announcements\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ConfiguredAnnouncementLanguagesApiTest extends WebTestCase
{
    public function testReturnsEnabledConfiguredLanguages(): void
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
        $configId = $this->response($client)['data']['id'];

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'ro-MD',
            'sortOrder' => 1,
            'segments' => [['sortOrder' => 1, 'type' => 'text', 'text' => 'Înregistrarea este deschisă.']],
            'enabled' => true,
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->json($client, 'POST', sprintf('/api/v1/admin/flight-definitions/%s/announcement-configs/%s/variants', $flightId, $configId), [
            'languageCode' => 'en',
            'sortOrder' => 2,
            'segments' => [['sortOrder' => 1, 'type' => 'text', 'text' => 'Check-in is open.']],
            'enabled' => false,
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', sprintf('/api/v1/flight-announcement-configs/languages?flightDefinitionId=%s&announcementType=check_in_opening', $flightId));
        self::assertResponseIsSuccessful();
        self::assertSame(['ro-MD'], $this->response($client)['data']['languages']);
    }

    public function testReturnsEmptyWhenNoConfigForType(): void
    {
        $client = static::createClient();
        $this->authenticate($client);
        $flightId = $this->createFlightDefinition($client, 'departure');

        $client->request('GET', sprintf('/api/v1/flight-announcement-configs/languages?flightDefinitionId=%s&announcementType=boarding_invitation', $flightId));
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->response($client)['data']['languages']);
    }

    public function testRejectsMissingParameters(): void
    {
        $client = static::createClient();
        $this->authenticate($client);

        $client->request('GET', '/api/v1/flight-announcement-configs/languages');
        self::assertResponseStatusCodeSame(422);
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
            'email' => 'config-languages-'.uniqid('', true).'@example.com',
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
