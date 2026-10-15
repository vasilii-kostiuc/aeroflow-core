<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishAnnouncementPlaybackRequest;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
use App\Tests\Support\RecordingPlaybackCommandPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishAnnouncementPlaybackRequestTest extends TestCase
{
    public function testPublishesNeutralPlaybackRequestForCreatedAnnouncement(): void
    {
        $announcement = $this->preparedAnnouncement();
        $announcementId = $announcement->getId()->toRfc4122();

        $announcements = $this->createStub(AnnouncementRepositoryInterface::class);
        $announcements->method('findById')->willReturn($announcement);

        $publisher = new RecordingPlaybackCommandPublisher();
        $handler = new PublishAnnouncementPlaybackRequest($announcements, $publisher);

        $handler(new AnnouncementCreated(
            $announcementId,
            'check_in_opening',
            $announcement->getFlightDefinitionId()->toRfc4122(),
            ['ro-MD', 'ru'],
            new DateTimeImmutable('2026-06-26T10:00:00+00:00'),
        ));

        self::assertCount(1, $publisher->commands);
        $message = $publisher->commands[0];
        self::assertInstanceOf(RequestAnnouncementPlayback::class, $message);

        self::assertNotSame('', $message->messageId);
        self::assertTrue(Uuid::isValid($message->messageId));
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame('check_in_opening', $message->type);
        self::assertSame(100, $message->priority);
        self::assertSame($announcement->getAudioSequence(), $message->audioSequence);
        self::assertNull($message->repeatRule);
        self::assertSame('2026-06-26T10:00:00+00:00', $message->occurredAt);
        self::assertSame(2, $message->schemaVersion);
        self::assertSame('announcement_playback.request', $message->command);
    }

    public function testPublishesSupplementaryAnnouncementAtPriority50(): void
    {
        $announcement = Announcement::createPrepared(
            AnnouncementType::Supplementary,
            null,
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [],
            null,
            [[
                'languageCode' => 'en',
                'sortOrder' => 1,
                'items' => [['type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()]],
            ]],
        );
        $announcementId = $announcement->getId()->toRfc4122();

        $announcements = $this->createStub(AnnouncementRepositoryInterface::class);
        $announcements->method('findById')->willReturn($announcement);

        $publisher = new RecordingPlaybackCommandPublisher();
        $handler = new PublishAnnouncementPlaybackRequest($announcements, $publisher);

        $handler(new AnnouncementCreated(
            $announcementId,
            'supplementary',
            null,
            ['en'],
            new DateTimeImmutable('2026-06-26T10:00:00+00:00'),
        ));

        self::assertCount(1, $publisher->commands);
        $message = $publisher->commands[0];
        self::assertInstanceOf(RequestAnnouncementPlayback::class, $message);
        self::assertSame('supplementary', $message->type);
        self::assertSame(50, $message->priority);
    }

    public function testDoesNothingWhenAnnouncementIsMissing(): void
    {
        $announcements = $this->createStub(AnnouncementRepositoryInterface::class);
        $announcements->method('findById')->willReturn(null);

        $publisher = new RecordingPlaybackCommandPublisher();
        $handler = new PublishAnnouncementPlaybackRequest($announcements, $publisher);

        $handler(new AnnouncementCreated(
            Uuid::v7()->toRfc4122(),
            'arrival',
            Uuid::v7()->toRfc4122(),
            ['ro-MD'],
            new DateTimeImmutable('2026-06-26T10:00:00+00:00'),
        ));

        self::assertSame([], $publisher->commands);
    }

    private function preparedAnnouncement(): Announcement
    {
        $languages = AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('ro-MD'),
            LanguageCode::fromString('ru'),
        );

        return Announcement::createPrepared(
            AnnouncementType::CheckInOpening,
            Uuid::v7()->toRfc4122(),
            $languages,
            [['id' => Uuid::v7()->toRfc4122(), 'code' => '1']],
            null,
            [[
                'languageCode' => 'ro-MD',
                'sortOrder' => 0,
                'items' => [['type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()]],
            ]],
            Uuid::v7()->toRfc4122(),
        );
    }
}
