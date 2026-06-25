<?php

declare(strict_types=1);

namespace App\Tests\Application\Localization;

use App\Localization\Infrastructure\Config\ConfiguredLanguageProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfiguredLanguageProviderTest extends TestCase
{
    public function testReturnsNormalizedSortedLanguages(): void
    {
        $provider = new ConfiguredLanguageProvider([
            ['code' => 'EN', 'name' => 'English', 'nativeName' => 'English', 'active' => true, 'sortOrder' => 2],
            ['code' => 'ro-md', 'name' => 'Romanian', 'nativeName' => 'Romana', 'active' => true, 'sortOrder' => 1],
        ]);

        $languages = $provider->all();

        self::assertSame('ro-MD', $languages[0]->code);
        self::assertSame('en', $languages[1]->code);
    }

    public function testRejectsDuplicateCodesAfterNormalization(): void
    {
        $provider = new ConfiguredLanguageProvider([
            ['code' => 'ro-MD', 'name' => 'Romanian'],
            ['code' => 'ro-md', 'name' => 'Romanian duplicate'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $provider->all();
    }
}
