<?php

declare(strict_types=1);

namespace App\Tests\Functional\Localization\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LanguageApiTest extends WebTestCase
{
    public function testListsConfiguredLanguages(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/languages');

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertSame('ro-MD', $payload['data'][0]['code']);
        self::assertSame('Romana', $payload['data'][0]['nativeName']);
        self::assertTrue($payload['data'][0]['active']);
    }
}
