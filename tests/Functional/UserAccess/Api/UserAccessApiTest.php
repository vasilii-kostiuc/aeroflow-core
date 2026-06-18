<?php

declare(strict_types=1);

namespace App\Tests\Functional\UserAccess\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserAccessApiTest extends WebTestCase
{
    public function testRegisterLoginRefreshAndLogoutFlow(): void
    {
        $client = static::createClient();
        $email = 'api-'.uniqid('', true).'@example.com';

        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => $email,
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(201);
        $registerPayload = $this->jsonResponse($client);
        self::assertTrue($registerPayload['success']);
        self::assertSame($email, $registerPayload['data']['user']['email']);
        self::assertNotEmpty($registerPayload['data']['accessToken']);
        self::assertNotEmpty($registerPayload['data']['refreshToken']);

        $this->jsonRequest($client, 'POST', '/api/v1/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        self::assertResponseIsSuccessful();
        $loginPayload = $this->jsonResponse($client);
        self::assertTrue($loginPayload['success']);
        self::assertSame($email, $loginPayload['data']['user']['email']);

        $this->jsonRequest($client, 'POST', '/api/v1/token/refresh', [
            'refreshToken' => $loginPayload['data']['refreshToken'],
        ]);

        self::assertResponseIsSuccessful();
        $refreshPayload = $this->jsonResponse($client);
        self::assertTrue($refreshPayload['success']);
        self::assertNotSame($loginPayload['data']['refreshToken'], $refreshPayload['data']['refreshToken']);

        $this->jsonRequest($client, 'POST', '/api/v1/token/refresh', [
            'refreshToken' => $loginPayload['data']['refreshToken'],
        ]);

        self::assertResponseStatusCodeSame(401);

        $this->jsonRequest($client, 'POST', '/api/v1/logout', [
            'refreshToken' => $refreshPayload['data']['refreshToken'],
        ]);

        self::assertResponseStatusCodeSame(204);
    }

    public function testRegisterReturnsConflictForDuplicateEmail(): void
    {
        $client = static::createClient();
        $email = 'duplicate-'.uniqid('', true).'@example.com';

        $payload = [
            'email' => $email,
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ];

        $this->jsonRequest($client, 'POST', '/api/v1/register', $payload);
        self::assertResponseStatusCodeSame(201);

        $this->jsonRequest($client, 'POST', '/api/v1/register', $payload);
        self::assertResponseStatusCodeSame(409);
        self::assertFalse($this->jsonResponse($client)['success']);
    }

    public function testLoginReturnsUnauthorizedForInvalidPassword(): void
    {
        $client = static::createClient();
        $email = 'invalid-password-'.uniqid('', true).'@example.com';

        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => $email,
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->jsonRequest($client, 'POST', '/api/v1/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertFalse($this->jsonResponse($client)['success']);
    }

    public function testRegisterValidationErrorsReturnUnprocessableEntity(): void
    {
        $client = static::createClient();

        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => 'not-an-email',
            'password' => 'short',
            'passwordConfirmation' => 'different',
        ]);

        self::assertResponseStatusCodeSame(422);
        $payload = $this->jsonResponse($client);
        self::assertFalse($payload['success']);
        self::assertNotEmpty($payload['errors']);
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
