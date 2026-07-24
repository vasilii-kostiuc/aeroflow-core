<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\LaunchSupplementaryAnnouncement\LaunchSupplementaryAnnouncementCommand;
use App\Announcements\Application\LaunchSupplementaryAnnouncement\LaunchSupplementaryAnnouncementHandler;
use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Entity\SupplementaryAnnouncementTemplate;
use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Exception\InactiveSupplementaryTemplateException;
use App\Announcements\Domain\Exception\SupplementaryTemplateNotFoundException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use App\Tests\Support\RecordingEventPublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LaunchSupplementaryAnnouncementHandlerTest extends TestCase
{
    private const string RO_ASSET = '019000aa-0000-7000-8000-000000000001';
    private const string RU_ASSET = '019000aa-0000-7000-8000-000000000002';

    public function testLaunchesPresetForRequestedLanguageSubset(): void
    {
        $template = SupplementaryAnnouncementTemplate::create('Безопасность', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => self::RO_ASSET],
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $template->pullEvents();

        $announcements = new InMemoryAnnouncementRepository();
        $events = new RecordingEventPublisher();
        $handler = new LaunchSupplementaryAnnouncementHandler(
            new InMemoryTemplateRepository($template),
            $announcements,
            new AllAssetsActiveLookup(),
            new UuidParser(),
            $events,
        );

        $result = $handler(new LaunchSupplementaryAnnouncementCommand($template->getId()->toRfc4122(), ['ru']));

        self::assertSame('supplementary', $result->type);
        self::assertNull($result->flightDefinitionId);
        self::assertSame(['ru'], $result->languages);
        self::assertSame(AnnouncementStatus::Prepared->value, $result->status);

        $sequence = $announcements->saved?->getAudioSequence() ?? [];
        self::assertCount(1, $sequence);
        self::assertSame('ru', $sequence[0]['languageCode']);
        self::assertSame(self::RU_ASSET, $sequence[0]['items'][0]['audioAssetId']);
        self::assertInstanceOf(AnnouncementCreated::class, $events->messages[0]);
        self::assertNull($events->messages[0]->flightDefinitionId);
    }

    public function testEmptyLanguageSelectionUsesAllConfiguredLanguages(): void
    {
        $template = SupplementaryAnnouncementTemplate::create('Безопасность', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => self::RO_ASSET],
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $announcements = new InMemoryAnnouncementRepository();
        $handler = new LaunchSupplementaryAnnouncementHandler(
            new InMemoryTemplateRepository($template),
            $announcements,
            new AllAssetsActiveLookup(),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        $result = $handler(new LaunchSupplementaryAnnouncementCommand($template->getId()->toRfc4122(), []));

        self::assertSame(['ro-MD', 'ru'], $result->languages);
    }

    public function testRejectsUnknownTemplate(): void
    {
        $handler = new LaunchSupplementaryAnnouncementHandler(
            new InMemoryTemplateRepository(null),
            new InMemoryAnnouncementRepository(),
            new AllAssetsActiveLookup(),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        $this->expectException(SupplementaryTemplateNotFoundException::class);
        $handler(new LaunchSupplementaryAnnouncementCommand(Uuid::v7()->toRfc4122(), ['ru']));
    }

    public function testRejectsInactiveTemplate(): void
    {
        $template = SupplementaryAnnouncementTemplate::create('Безопасность', [
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $template->deactivate();
        $announcements = new InMemoryAnnouncementRepository();
        $handler = new LaunchSupplementaryAnnouncementHandler(
            new InMemoryTemplateRepository($template),
            $announcements,
            new AllAssetsActiveLookup(),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        try {
            $handler(new LaunchSupplementaryAnnouncementCommand($template->getId()->toRfc4122(), ['ru']));
            self::fail('Expected InactiveSupplementaryTemplateException.');
        } catch (InactiveSupplementaryTemplateException) {
            self::assertNull($announcements->saved);
        }
    }

    public function testRejectsWhenPresetAssetWasDeactivated(): void
    {
        $template = SupplementaryAnnouncementTemplate::create('Безопасность', [
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $announcements = new InMemoryAnnouncementRepository();
        $handler = new LaunchSupplementaryAnnouncementHandler(
            new InMemoryTemplateRepository($template),
            $announcements,
            new NoAssetsActiveLookup(),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        try {
            $handler(new LaunchSupplementaryAnnouncementCommand($template->getId()->toRfc4122(), ['ru']));
            self::fail('Expected AudioAssetUnavailableException.');
        } catch (AudioAssetUnavailableException) {
            self::assertNull($announcements->saved);
        }
    }
}

final class InMemoryTemplateRepository implements SupplementaryAnnouncementTemplateRepositoryInterface
{
    public function __construct(private ?SupplementaryAnnouncementTemplate $template)
    {
    }

    public function save(SupplementaryAnnouncementTemplate $template): void
    {
        $this->template = $template;
    }

    public function findById(Uuid $id): ?SupplementaryAnnouncementTemplate
    {
        return $this->template;
    }

    public function findAll(?bool $active = null): array
    {
        return $this->template === null ? [] : [$this->template];
    }
}

final class InMemoryAnnouncementRepository implements AnnouncementRepositoryInterface
{
    public ?Announcement $saved = null;

    public function save(Announcement $announcement): void
    {
        $this->saved = $announcement;
    }

    public function findById(Uuid $id): ?Announcement
    {
        return $this->saved;
    }

    public function findActiveContinuationByOccurrenceId(Uuid $flightOccurrenceId): ?Announcement
    {
        return null;
    }

    public function findLatest(int $limit = 100): array
    {
        return $this->saved === null ? [] : [$this->saved];
    }
}

final class AllAssetsActiveLookup implements AudioPromptLookupInterface
{
    public function activeAssetId(string $kind, string $value, string $languageCode): ?string
    {
        return null;
    }

    public function isActiveAsset(string $audioAssetId): bool
    {
        return true;
    }
}

final class NoAssetsActiveLookup implements AudioPromptLookupInterface
{
    public function activeAssetId(string $kind, string $value, string $languageCode): ?string
    {
        return null;
    }

    public function isActiveAsset(string $audioAssetId): bool
    {
        return false;
    }
}
