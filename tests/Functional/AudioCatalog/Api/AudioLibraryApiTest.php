<?php

declare(strict_types=1);

namespace App\Tests\Functional\AudioCatalog\Api;

use App\Tests\Support\ActsAsUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Audio library (task 031): folders, asset titles and moving assets between
 * folders through the admin API.
 */
final class AudioLibraryApiTest extends WebTestCase
{
    use ActsAsUser;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->authenticate();
    }

    public function testMigrationSeedsStarterFolders(): void
    {
        $this->client->request('GET', '/api/v1/admin/audio-folders');

        self::assertResponseIsSuccessful();
        $topLevel = array_column(
            array_filter($this->data(), static fn (array $f): bool => null === $f['parentId']),
            'name',
        );
        foreach (['Вылеты', 'Прибытие', 'Дополнительно', 'Экстренные', 'Числа', 'Гонги', 'Музыка'] as $name) {
            self::assertContains($name, $topLevel);
        }
    }

    public function testFolderLifecycle(): void
    {
        $departures = $this->createFolder('Тестовые вылеты');
        $flight = $this->createFolder('SU 1234', $departures);

        // Same name under the same parent, case-insensitively.
        $this->json('POST', '/api/v1/admin/audio-folders', ['name' => 'su 1234', 'parentId' => $departures]);
        self::assertResponseStatusCodeSame(409);

        // Moving a folder into its own subfolder.
        $this->json('PATCH', '/api/v1/admin/audio-folders/'.$departures, ['name' => 'Тестовые вылеты', 'parentId' => $flight]);
        self::assertResponseStatusCodeSame(422);

        $this->json('PATCH', '/api/v1/admin/audio-folders/'.$flight, ['name' => 'SU 1234 Москва', 'parentId' => null]);
        self::assertResponseIsSuccessful();
        self::assertSame(['id' => $flight, 'name' => 'SU 1234 Москва', 'parentId' => null], $this->data());

        $this->client->request('DELETE', '/api/v1/admin/audio-folders/'.$flight);
        self::assertResponseStatusCodeSame(204);

        $this->client->request('DELETE', '/api/v1/admin/audio-folders/'.$flight);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownParentGives404(): void
    {
        $this->json('POST', '/api/v1/admin/audio-folders', ['name' => 'X', 'parentId' => Uuid::v7()->toRfc4122()]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testUploadDefaultsTitleAndRootFolder(): void
    {
        $asset = $this->upload('gate-a12-en.wav');

        self::assertSame('gate-a12-en.wav', $asset['name']);
        self::assertSame('gate a12 en', $asset['title']);
        self::assertNull($asset['folderId']);
    }

    public function testUploadWithTitleIntoFolderAndRenameMove(): void
    {
        $gates = $this->createFolder('Выходы');
        $asset = $this->upload('gate-a12-en.wav', ['title' => 'Выход A12', 'folderId' => $gates]);

        self::assertSame('Выход A12', $asset['title']);
        self::assertSame($gates, $asset['folderId']);

        $this->client->request('GET', '/api/v1/admin/audio-assets');
        $listed = array_values(array_filter($this->data(), static fn (array $a): bool => $a['id'] === $asset['id']));
        self::assertSame('Выход A12', $listed[0]['title']);

        $this->json('PATCH', '/api/v1/admin/audio-assets/'.$asset['id'], ['title' => 'Выход A12 (англ.)', 'folderId' => null]);
        self::assertResponseIsSuccessful();
        self::assertSame('Выход A12 (англ.)', $this->data()['title']);
        self::assertNull($this->data()['folderId']);
        self::assertSame('gate-a12-en.wav', $this->data()['name']);
    }

    public function testDeletingFolderWithActiveAssetIsRejected(): void
    {
        $chimes = $this->createFolder('Тестовые гонги');
        $this->upload('gong.wav', ['folderId' => $chimes]);

        $this->client->request('DELETE', '/api/v1/admin/audio-folders/'.$chimes);

        self::assertResponseStatusCodeSame(409);
    }

    public function testAssetUpdateValidation(): void
    {
        $asset = $this->upload('a.wav');

        $this->json('PATCH', '/api/v1/admin/audio-assets/'.$asset['id'], ['title' => '   ']);
        self::assertResponseStatusCodeSame(422);

        $this->json('PATCH', '/api/v1/admin/audio-assets/'.$asset['id'], ['title' => 'x', 'folderId' => Uuid::v7()->toRfc4122()]);
        self::assertResponseStatusCodeSame(404);

        $this->json('PATCH', '/api/v1/admin/audio-assets/'.Uuid::v7()->toRfc4122(), ['title' => 'x']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testMovesSeveralAssetsAtOnce(): void
    {
        $gongs = $this->createFolder('Тестовые гонги');
        $a = $this->upload('gong-1.wav');
        $b = $this->upload('gong-2.wav');

        $this->json('POST', '/api/v1/admin/audio-assets:move', ['assetIds' => [$a['id'], $b['id']], 'folderId' => $gongs]);
        self::assertResponseIsSuccessful();
        self::assertSame([$gongs, $gongs], array_column($this->data(), 'folderId'));

        // All or nothing: an unknown id leaves the known one where it was.
        $this->json('POST', '/api/v1/admin/audio-assets:move', ['assetIds' => [$a['id'], Uuid::v7()->toRfc4122()], 'folderId' => null]);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/api/v1/admin/audio-assets');
        $listed = array_values(array_filter($this->data(), static fn (array $asset): bool => $asset['id'] === $a['id']));
        self::assertSame($gongs, $listed[0]['folderId']);

        $this->json('POST', '/api/v1/admin/audio-assets:move', ['assetIds' => []]);
        self::assertResponseStatusCodeSame(422);
    }

    private function createFolder(string $name, ?string $parentId = null): string
    {
        $this->json('POST', '/api/v1/admin/audio-folders', ['name' => $name, 'parentId' => $parentId]);
        self::assertResponseStatusCodeSame(201);

        return $this->data()['id'];
    }

    /**
     * @param array<string, string> $fields
     *
     * @return array<string, mixed>
     */
    private function upload(string $fileName, array $fields = []): array
    {
        $path = tempnam(sys_get_temp_dir(), 'audio-asset-');
        self::assertIsString($path);
        file_put_contents(
            $path,
            "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x44\xAC\x00\x00\x88\x58\x01\x00\x02\x00\x10\x00data\x00\x00\x00\x00",
        );

        $this->client->request(
            'POST',
            '/api/v1/admin/audio-assets',
            ['languageCode' => 'en'] + $fields,
            ['file' => new UploadedFile($path, $fileName, 'audio/wav', null, true)],
        );
        self::assertResponseStatusCodeSame(201);

        return $this->data();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function json(string $method, string $uri, array $body): void
    {
        $this->client->request($method, $uri, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function authenticate(): void
    {
        $this->actAs($this->client);
    }

    /**
     * @return array<mixed>
     */
    private function data(): array
    {
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $body['data'];
    }
}
