<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Console\AudioLibrary;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\Shared\Infrastructure\Console\AudioLibrary\AssetUsage;
use App\Shared\Infrastructure\Console\AudioLibrary\AudioLibraryPlanner;
use PHPUnit\Framework\TestCase;

final class AudioLibraryPlannerTest extends TestCase
{
    public function testPlacesAssetsByUsageAndTitlesThem(): void
    {
        $plan = new AudioLibraryPlanner()->plan(
            [
                $this->asset('fire', 'tts-en-attention-a-fire.wav'),
                $this->asset('taxi', 'tts-ru-speech.wav'),
                $this->asset('board', 'tts-en-boarding-5f151.wav'),
                $this->asset('land', 'tts-ro-md-cursa-5f152.wav'),
            ],
            [
                new AssetUsage('fire', AssetUsage::EMERGENCY, 'Пожар'),
                new AssetUsage('taxi', AssetUsage::SUPPLEMENTARY, 'Такси'),
                new AssetUsage('board', AssetUsage::DEPARTURE, '5F151', "Boarding   for flight\n5F151."),
                new AssetUsage('land', AssetUsage::ARRIVAL, '5F152', 'Cursa 5F152 a aterizat.'),
            ],
        );

        $byId = array_column(array_map(static fn ($item) => (array) $item, $plan['planned']), null, 'assetId');
        self::assertSame(['Экстренные'], $byId['fire']['folderPath']);
        self::assertSame('Пожар', $byId['fire']['newTitle']);
        self::assertSame(['Дополнительно'], $byId['taxi']['folderPath']);
        self::assertSame(['Вылеты', '5F151'], $byId['board']['folderPath']);
        self::assertSame('Boarding for flight 5F151.', $byId['board']['newTitle']);
        self::assertSame(['Прибытие', '5F152'], $byId['land']['folderPath']);
    }

    public function testKeepsWhatAnAdminAlreadyArranged(): void
    {
        $plan = new AudioLibraryPlanner()->plan(
            [
                // Already in a folder, default title: only the title changes.
                $this->asset('a', 'tts-ru-speech.wav', folderId: 'f1'),
                // Renamed by an admin, no folder: only the folder changes.
                $this->asset('b', 'tts-ru-speech.wav', title: 'Такси (рус.)'),
                // Both arranged already: nothing to do.
                $this->asset('c', 'tts-ru-speech.wav', title: 'Своё', folderId: 'f1'),
            ],
            [
                new AssetUsage('a', AssetUsage::SUPPLEMENTARY, 'Такси'),
                new AssetUsage('b', AssetUsage::SUPPLEMENTARY, 'Такси'),
                new AssetUsage('c', AssetUsage::SUPPLEMENTARY, 'Такси'),
            ],
        );

        self::assertCount(2, $plan['planned']);
        [$a, $b] = $plan['planned'];
        self::assertNull($a->folderPath);
        self::assertSame('Такси', $a->newTitle);
        self::assertSame(['Дополнительно'], $b->folderPath);
        self::assertNull($b->newTitle);
    }

    public function testLeavesUnusedAndAmbiguousAssetsAlone(): void
    {
        $plan = new AudioLibraryPlanner()->plan(
            [
                $this->asset('song', 'Do I Wanna Know.mp3'),
                $this->asset('shared', 'tts-en-welcome.wav'),
                $this->asset('twice', 'tts-en-5f151.wav'),
            ],
            [
                new AssetUsage('shared', AssetUsage::DEPARTURE, '5F151', 'Welcome'),
                new AssetUsage('shared', AssetUsage::DEPARTURE, 'H4401', 'Welcome'),
                // The same flight twice (ru segment reused) is not ambiguous.
                new AssetUsage('twice', AssetUsage::DEPARTURE, '5F151', 'Text'),
                new AssetUsage('twice', AssetUsage::DEPARTURE, '5F151', 'Text'),
            ],
        );

        self::assertCount(1, $plan['planned']);
        self::assertSame('twice', $plan['planned'][0]->assetId);
        self::assertSame(['song'], array_column($plan['skipped'][AudioLibraryPlanner::SKIP_UNUSED], 'id'));
        self::assertSame(['shared'], array_column($plan['skipped'][AudioLibraryPlanner::SKIP_AMBIGUOUS], 'id'));
    }

    private function asset(string $id, string $name, ?string $title = null, ?string $folderId = null): AudioAssetResult
    {
        return new AudioAssetResult(
            id: $id,
            name: $name,
            title: $title ?? AudioAsset::titleFromFileName($name),
            folderId: $folderId,
            languageCode: 'ru',
            active: true,
            mimeType: 'audio/wav',
            sizeBytes: 10,
            source: 'generated',
        );
    }
}
