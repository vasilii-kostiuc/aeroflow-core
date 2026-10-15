<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishEmergencyModeActivation;
use App\Announcements\Application\LaunchEmergencyAnnouncement\LaunchEmergencyAnnouncementCommand;
use App\Announcements\Application\LaunchEmergencyAnnouncement\LaunchEmergencyAnnouncementHandler;
use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Entity\EmergencyAnnouncementTemplate;
use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Exception\EmergencyTemplateNotFoundException;
use App\Announcements\Domain\Exception\InactiveEmergencyTemplateException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use App\Shared\Application\Uuid\UuidParser;
use App\Tests\Support\RecordingEventPublisher;
use App\Tests\Support\RecordingPlaybackCommandPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LaunchEmergencyAnnouncementHandlerTest extends TestCase
{
    private const string RO_ASSET = '019000bb-0000-7000-8000-000000000001';
    private const string RU_ASSET = '019000bb-0000-7000-8000-000000000002';

    public function testLaunchesPresetAtThePreemptivePriorityWithoutAFlight(): void
    {
        $template = EmergencyAnnouncementTemplate::create('Пожарная тревога', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => self::RO_ASSET],
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $template->pullEvents();

        $announcements = new RecordingEmergencyAnnouncementRepository();
        $events = new RecordingEventPublisher();
        $handler = new LaunchEmergencyAnnouncementHandler(
            new InMemoryEmergencyTemplateRepository($template),
            $announcements,
            new EmergencyAssetsActiveLookup(true),
            new UuidParser(),
            $events,
        );

        $result = $handler(new LaunchEmergencyAnnouncementCommand($template->getId()->toRfc4122(), ['ru']));

        self::assertSame('emergency', $result->type);
        self::assertNull($result->flightDefinitionId);
        self::assertSame(['ru'], $result->languages);
        self::assertSame(AnnouncementStatus::Prepared->value, $result->status);
        self::assertSame(1000, $announcements->saved?->getType()->playbackPriority());

        $sequence = $announcements->saved?->getAudioSequence() ?? [];
        self::assertCount(1, $sequence);
        self::assertSame(self::RU_ASSET, $sequence[0]['items'][0]['audioAssetId']);
        self::assertInstanceOf(AnnouncementCreated::class, $events->messages[0]);
    }

    public function testEmptyLanguageSelectionUsesAllConfiguredLanguages(): void
    {
        $template = EmergencyAnnouncementTemplate::create('Эвакуация', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => self::RO_ASSET],
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $handler = new LaunchEmergencyAnnouncementHandler(
            new InMemoryEmergencyTemplateRepository($template),
            new RecordingEmergencyAnnouncementRepository(),
            new EmergencyAssetsActiveLookup(true),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        $result = $handler(new LaunchEmergencyAnnouncementCommand($template->getId()->toRfc4122(), []));

        self::assertSame(['ro-MD', 'ru'], $result->languages);
    }

    public function testRejectsUnknownTemplate(): void
    {
        $handler = new LaunchEmergencyAnnouncementHandler(
            new InMemoryEmergencyTemplateRepository(null),
            new RecordingEmergencyAnnouncementRepository(),
            new EmergencyAssetsActiveLookup(true),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        $this->expectException(EmergencyTemplateNotFoundException::class);
        $handler(new LaunchEmergencyAnnouncementCommand(Uuid::v7()->toRfc4122(), ['ru']));
    }

    public function testRejectsInactiveTemplate(): void
    {
        $template = EmergencyAnnouncementTemplate::create('Эвакуация', [
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $template->deactivate();
        $announcements = new RecordingEmergencyAnnouncementRepository();
        $handler = new LaunchEmergencyAnnouncementHandler(
            new InMemoryEmergencyTemplateRepository($template),
            $announcements,
            new EmergencyAssetsActiveLookup(true),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        try {
            $handler(new LaunchEmergencyAnnouncementCommand($template->getId()->toRfc4122(), ['ru']));
            self::fail('Expected InactiveEmergencyTemplateException.');
        } catch (InactiveEmergencyTemplateException) {
            self::assertNull($announcements->saved);
        }
    }

    public function testRejectsWhenPresetAssetWasDeactivated(): void
    {
        $template = EmergencyAnnouncementTemplate::create('Эвакуация', [
            ['languageCode' => 'ru', 'audioAssetId' => self::RU_ASSET],
        ]);
        $announcements = new RecordingEmergencyAnnouncementRepository();
        $handler = new LaunchEmergencyAnnouncementHandler(
            new InMemoryEmergencyTemplateRepository($template),
            $announcements,
            new EmergencyAssetsActiveLookup(false),
            new UuidParser(),
            new RecordingEventPublisher(),
        );

        try {
            $handler(new LaunchEmergencyAnnouncementCommand($template->getId()->toRfc4122(), ['ru']));
            self::fail('Expected AudioAssetUnavailableException.');
        } catch (AudioAssetUnavailableException) {
            self::assertNull($announcements->saved);
        }
    }

    public function testEmergencyCreationAlsoActivatesTheMode(): void
    {
        $publisher = new RecordingPlaybackCommandPublisher();
        $announcementId = Uuid::v7()->toRfc4122();

        new PublishEmergencyModeActivation($publisher)(new AnnouncementCreated(
            $announcementId,
            AnnouncementType::Emergency->value,
            null,
            ['ru'],
            new DateTimeImmutable('2026-09-23T10:00:00+00:00'),
        ));

        self::assertCount(1, $publisher->commands);
        $message = $publisher->commands[0];
        self::assertInstanceOf(ActivateEmergencyMode::class, $message);
        self::assertSame('emergency_mode.activate', $message->command);
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame('2026-09-23T10:00:00+00:00', $message->occurredAt);
    }

    public function testOrdinaryAnnouncementDoesNotActivateTheMode(): void
    {
        $publisher = new RecordingPlaybackCommandPublisher();

        foreach ([AnnouncementType::Supplementary, AnnouncementType::CheckInOpening] as $type) {
            new PublishEmergencyModeActivation($publisher)(new AnnouncementCreated(
                Uuid::v7()->toRfc4122(),
                $type->value,
                null,
                ['ru'],
                new DateTimeImmutable(),
            ));
        }

        self::assertSame([], $publisher->commands);
    }
}

final class InMemoryEmergencyTemplateRepository implements EmergencyAnnouncementTemplateRepositoryInterface
{
    public function __construct(private ?EmergencyAnnouncementTemplate $template)
    {
    }

    public function save(EmergencyAnnouncementTemplate $template): void
    {
        $this->template = $template;
    }

    public function findById(Uuid $id): ?EmergencyAnnouncementTemplate
    {
        return $this->template;
    }

    public function findAll(?bool $active = null): array
    {
        return $this->template === null ? [] : [$this->template];
    }
}

final class RecordingEmergencyAnnouncementRepository implements AnnouncementRepositoryInterface
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

final class EmergencyAssetsActiveLookup implements AudioPromptLookupInterface
{
    public function __construct(private bool $active)
    {
    }

    public function activeAssetId(string $kind, string $value, string $languageCode): ?string
    {
        return null;
    }

    public function isActiveAsset(string $audioAssetId): bool
    {
        return $this->active;
    }
}
