<?php

declare(strict_types=1);

namespace App\Tests\Functional\UserAccess\Api;

use App\Tests\Support\ActsAsUser;
use App\UserAccess\Domain\Enum\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The access matrix of task 033, checked through security.yaml as a request
 * sees it: 401 without a token, 403 when the role does not allow it, anything
 * else (200, 404, 422 …) when the role gets through to the controller.
 */
final class RoleAccessApiTest extends WebTestCase
{
    use ActsAsUser;

    /**
     * @return iterable<string, array{string, string, bool, bool}> method, path, dispatcher allowed, no-role allowed
     */
    public static function endpoints(): iterable
    {
        yield 'me' => ['GET', '/api/v1/me', true, true];
        yield 'dispatcher board' => ['GET', '/api/v1/dispatcher/board', true, false];
        yield 'playback queue' => ['GET', '/api/v1/dispatcher/playback-queue', true, false];
        yield 'emergency deactivate' => ['POST', '/api/v1/dispatcher/emergency-announcements:deactivate', true, false];
        yield 'ensure occurrence' => ['POST', '/api/v1/flight-occurrences:ensure-manual', true, false];
        yield 'open check-in' => ['POST', '/api/v1/flight-occurrences/00000000-0000-7000-8000-000000000000/check-in:open', true, false];
        yield 'cancel announcement' => ['POST', '/api/v1/announcements/00000000-0000-7000-8000-000000000000/cancel', true, false];
        yield 'read flights' => ['GET', '/api/v1/flight-definitions', true, false];
        yield 'read airports' => ['GET', '/api/v1/airports', true, false];
        yield 'read gates' => ['GET', '/api/v1/admin/gates', true, false];
        yield 'read counters' => ['GET', '/api/v1/admin/check-in-counters', true, false];
        yield 'write flights' => ['POST', '/api/v1/flight-definitions', false, false];
        yield 'write gates' => ['POST', '/api/v1/admin/gates', false, false];
        yield 'audio library' => ['GET', '/api/v1/admin/audio-assets', false, false];
        yield 'audio folders' => ['POST', '/api/v1/admin/audio-folders', false, false];
        yield 'presets' => ['GET', '/api/v1/admin/emergency-announcement-templates', false, false];
        yield 'users' => ['GET', '/api/v1/admin/users', false, false];
        yield 'strict occurrence create' => ['POST', '/api/v1/flight-occurrences', false, false];
    }

    #[DataProvider('endpoints')]
    public function testAccessByRole(string $method, string $path, bool $dispatcherAllowed, bool $noRoleAllowed): void
    {
        $client = static::createClient();

        $client->request($method, $path, [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertResponseStatusCodeSame(401, 'without a token');

        foreach ([[UserRole::Admin, true], [UserRole::Dispatcher, $dispatcherAllowed], [null, $noRoleAllowed]] as [$role, $allowed]) {
            $this->actAs($client, $role);
            $client->request($method, $path, [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
            $status = $client->getResponse()->getStatusCode();
            $who = $role?->code() ?? 'no role';

            if ($allowed) {
                self::assertNotContains($status, [401, 403], sprintf('%s should reach %s %s, got %d', $who, $method, $path, $status));
            } else {
                self::assertSame(403, $status, sprintf('%s must be refused %s %s', $who, $method, $path));
            }
        }
    }
}
