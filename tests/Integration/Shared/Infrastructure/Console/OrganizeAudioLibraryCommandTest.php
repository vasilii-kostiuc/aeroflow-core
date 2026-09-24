<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Console;

use App\AudioCatalog\Domain\Entity\AudioAsset;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

/**
 * Runs `app:audio-library:organize` against the real schema: the usage SQL across
 * presets and flight configs, then the writes through the Audio Catalog use cases.
 */
final class OrganizeAudioLibraryCommandTest extends KernelTestCase
{
    public function testSortsAssetsByUsageAndIsSafeToRerun(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $assets = $container->get(AudioAssetRepositoryInterface::class);
        $db = $container->get(Connection::class);

        $preset = $this->asset($assets, 'tts-ru-speech.wav');
        $flight = $this->asset($assets, 'tts-en-attention-please.wav');
        $song = $this->asset($assets, 'Do I Wanna Know.mp3');
        $flightNumber = 'ZZ'.random_int(100, 999);

        $db->insert('supplementary_announcement_template', [
            'id' => Uuid::v7()->toRfc4122(),
            'name' => 'Такси (тест)',
            'variants' => json_encode([['languageCode' => 'ru', 'audioAssetId' => $preset->getId()->toRfc4122()]]),
            'active' => 'true',
            'created_at' => '2026-09-24 10:00:00',
            'updated_at' => '2026-09-24 10:00:00',
        ]);
        $this->flightSegment($db, $flightNumber, $flight, 'Attention please. Check-in is open.');

        $tester = new CommandTester(new Application(self::$kernel)->find('app:audio-library:organize'));

        $tester->execute(['--dry-run' => true]);
        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Вылеты / '.$flightNumber, $tester->getDisplay());
        $this->refresh($preset, $flight);
        self::assertNull($preset->getFolderId(), 'a dry run changes nothing');

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        $this->refresh($preset, $flight, $song);

        $folders = $container->get(AudioFolderRepositoryInterface::class);
        self::assertSame('Дополнительно', $folders->findById($preset->getFolderId() ?? Uuid::v7())?->getName());
        self::assertSame('Такси (тест)', $preset->getTitle());
        $flightFolder = $folders->findById($flight->getFolderId() ?? Uuid::v7());
        self::assertSame($flightNumber, $flightFolder?->getName());
        self::assertSame('Вылеты', $folders->findById($flightFolder->getParentId() ?? Uuid::v7())?->getName());
        self::assertSame('Attention please. Check-in is open.', $flight->getTitle());
        self::assertNull($song->getFolderId(), 'unused assets stay where they are');

        $tester->execute([]);
        self::assertStringContainsString('Nothing to organize', $tester->getDisplay());
    }

    private function asset(AudioAssetRepositoryInterface $assets, string $name): AudioAsset
    {
        $asset = AudioAsset::register($name, LanguageCode::fromString('ru'));
        $assets->save($asset);

        return $asset;
    }

    private function flightSegment(Connection $db, string $flightNumber, AudioAsset $asset, string $text): void
    {
        $now = '2026-09-24 10:00:00';
        $flightId = Uuid::v7()->toRfc4122();
        $configId = Uuid::v7()->toRfc4122();
        $variantId = Uuid::v7()->toRfc4122();
        $db->insert('flight_definition', [
            'id' => $flightId, 'flight_number' => $flightNumber, 'direction' => 'departure',
            'origin_airport_code' => 'RMO', 'destination_airport_code' => 'FCO', 'active' => 'true',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $db->insert('flight_announcement_config', [
            'id' => $configId, 'flight_definition_id' => $flightId, 'announcement_type' => 'check_in_opening',
            'enabled' => 'true', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $db->insert('announcement_variant', [
            'id' => $variantId, 'config_id' => $configId, 'language_code' => 'en', 'sort_order' => 1,
            'enabled' => 'true', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $db->insert('announcement_template_segment', [
            'id' => Uuid::v7()->toRfc4122(), 'variant_id' => $variantId, 'sort_order' => 1, 'type' => 'text',
            'audio_asset_id' => $asset->getId()->toRfc4122(), 'text' => $text,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function refresh(AudioAsset ...$assets): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        foreach ($assets as $asset) {
            $em->refresh($asset);
        }
    }
}
