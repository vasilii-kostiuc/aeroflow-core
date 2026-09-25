<?php

declare(strict_types=1);

namespace App\Tests\Functional\UserAccess\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Sign-in flow and user management (task 033): accounts are created by an
 * administrator, there is no self-registration.
 */
final class UserAccessApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testSelfRegistrationIsGone(): void
    {
        $client = static::createClient();

        $this->jsonRequest($client, 'POST', '/api/v1/register', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testLoginRefreshAndLogoutFlow(): void
    {
        $client = static::createClient();
        $this->actAs($client);
        $email = $this->createUser($client, 'dispatcher');

        $this->jsonRequest($client, 'POST', '/api/v1/login', ['email' => $email, 'password' => 'password123']);
        self::assertResponseIsSuccessful();
        $loginPayload = $this->jsonResponse($client);
        self::assertSame($email, $loginPayload['data']['user']['email']);
        self::assertContains('ROLE_DISPATCHER', $loginPayload['data']['user']['roles']);

        $this->jsonRequest($client, 'POST', '/api/v1/token/refresh', ['refreshToken' => $loginPayload['data']['refreshToken']]);
        self::assertResponseIsSuccessful();
        $refreshPayload = $this->jsonResponse($client);
        self::assertNotSame($loginPayload['data']['refreshToken'], $refreshPayload['data']['refreshToken']);

        $this->jsonRequest($client, 'POST', '/api/v1/token/refresh', ['refreshToken' => $loginPayload['data']['refreshToken']]);
        self::assertResponseStatusCodeSame(401);

        $this->jsonRequest($client, 'POST', '/api/v1/logout', ['refreshToken' => $refreshPayload['data']['refreshToken']]);
        self::assertResponseStatusCodeSame(204);
    }

    public function testLoginReturnsUnauthorizedForInvalidPassword(): void
    {
        $client = static::createClient();
        $this->actAs($client);
        $email = $this->createUser($client, 'dispatcher');

        $this->jsonRequest($client, 'POST', '/api/v1/login', ['email' => $email, 'password' => 'wrong-password']);

        self::assertResponseStatusCodeSame(401);
        self::assertFalse($this->jsonResponse($client)['success']);
    }

    public function testCreateUserRejectsDuplicatesAndInvalidInput(): void
    {
        $client = static::createClient();
        $this->actAs($client);
        $email = $this->createUser($client, 'dispatcher');

        $this->jsonRequest($client, 'POST', '/api/v1/admin/users', ['email' => $email, 'password' => 'password123', 'role' => 'dispatcher']);
        self::assertResponseStatusCodeSame(409);

        $this->jsonRequest($client, 'POST', '/api/v1/admin/users', ['email' => 'not-an-email', 'password' => 'short', 'role' => 'pilot']);
        self::assertResponseStatusCodeSame(422);
        self::assertNotEmpty($this->jsonResponse($client)['errors']);
    }

    public function testAdministratorManagesRolesButNeverLeavesTheSystemWithoutOne(): void
    {
        $client = static::createClient();
        $existingAdmins = 0;

        $admin = $this->actAs($client);
        $this->jsonRequest($client, 'GET', '/api/v1/admin/users');
        self::assertResponseIsSuccessful();
        foreach ($this->jsonResponse($client)['data'] as $user) {
            $existingAdmins += 'admin' === $user['role'] ? 1 : 0;
        }
        $adminId = (string) $admin->getId();

        if (1 === $existingAdmins) {
            $this->jsonRequest($client, 'PATCH', '/api/v1/admin/users/'.$adminId, ['role' => 'dispatcher']);
            self::assertResponseStatusCodeSame(409);
        }

        $secondEmail = $this->createUser($client, 'admin');
        $this->jsonRequest($client, 'PATCH', '/api/v1/admin/users/'.$adminId, ['role' => 'dispatcher']);
        self::assertResponseIsSuccessful();
        self::assertSame('dispatcher', $this->jsonResponse($client)['data']['role']);
        self::assertNotSame('', $secondEmail);

        // Now a dispatcher: user management is closed to them at once.
        $this->jsonRequest($client, 'GET', '/api/v1/admin/users');
        self::assertResponseStatusCodeSame(403);
    }

    public function testRemovingTheRoleClosesAccessButKeepsMe(): void
    {
        $client = static::createClient();
        $this->actAs($client, null);

        $this->jsonRequest($client, 'GET', '/api/v1/me');
        self::assertResponseIsSuccessful();
        self::assertNull($this->jsonResponse($client)['data']['role']);

        $this->jsonRequest($client, 'GET', '/api/v1/dispatcher/board');
        self::assertResponseStatusCodeSame(403);
    }

    public function testSettingAPasswordRevokesTheUsersSessions(): void
    {
        $client = static::createClient();
        $this->actAs($client);
        $email = $this->createUser($client, 'dispatcher');
        $this->jsonRequest($client, 'GET', '/api/v1/admin/users');
        $userId = array_values(array_filter($this->jsonResponse($client)['data'], static fn (array $u): bool => $u['email'] === $email))[0]['id'];

        $this->jsonRequest($client, 'POST', '/api/v1/login', ['email' => $email, 'password' => 'password123']);
        $oldRefreshToken = $this->jsonResponse($client)['data']['refreshToken'];

        $this->jsonRequest($client, 'POST', '/api/v1/admin/users/'.$userId.'/password', ['password' => 'new-password-1']);
        self::assertResponseStatusCodeSame(204);

        $this->jsonRequest($client, 'POST', '/api/v1/token/refresh', ['refreshToken' => $oldRefreshToken]);
        self::assertResponseStatusCodeSame(401);

        $this->jsonRequest($client, 'POST', '/api/v1/login', ['email' => $email, 'password' => 'new-password-1']);
        self::assertResponseIsSuccessful();
    }

    /** Creates a user through the admin API as the administrator the client is signed in as. */
    private function createUser(KernelBrowser $client, ?string $role): string
    {
        $email = 'api-'.uniqid('', true).'@example.com';
        $this->jsonRequest($client, 'POST', '/api/v1/admin/users', ['email' => $email, 'password' => 'password123', 'role' => $role]);
        self::assertResponseStatusCodeSame(201);
        self::assertSame($role, $this->jsonResponse($client)['data']['role']);

        return $email;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(KernelBrowser $client, string $method, string $uri, array $payload = []): void
    {
        $client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            [] === $payload && 'GET' === $method ? null : json_encode($payload, JSON_THROW_ON_ERROR),
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
