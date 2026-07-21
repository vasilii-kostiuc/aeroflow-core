<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishAnnouncementPlaybackRequest;
use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
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

        $publisher = new RecordingPlaybackRequestPublisher();
        $handler = new PublishAnnouncementPlaybackRequest($announcements, $publisher);

        $handler(new AnnouncementCreated(
            $announcementId,
            'check_in_opening',
            $announcement->getFlightDefinitionId()->toRfc4122(),
            ['ro-MD', 'ru'],
            new DateTimeImmutable('2026-06-26T10:00:00+00:00'),
        ));

        self::assertCount(1, $publisher->messages);
        $message = $publisher->messages[0];

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

    public function testDoesNothingWhenAnnouncementIsMissing(): void
    {
        $announcements = $this->createStub(AnnouncementRepositoryInterface::class);
        $announcements->method('findById')->willReturn(null);

        $publisher = new RecordingPlaybackRequestPublisher();
        $handler = new PublishAnnouncementPlaybackRequest($announcements, $publisher);

        $handler(new AnnouncementCreated(
            Uuid::v7()->toRfc4122(),
            'arrival',
            Uuid::v7()->toRfc4122(),
            ['ro-MD'],
            new DateTimeImmutable('2026-06-26T10:00:00+00:00'),
        ));

        self::assertSame([], $publisher->messages);
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

final class RecordingPlaybackRequestPublisher implements PlaybackRequestPublisherInterface
{
    /** @var list<RequestAnnouncementPlayback> */
    public array $messages = [];

    /** @var list<CancelAnnouncementPlayback> */
    public array $cancels = [];

    public function publish(RequestAnnouncementPlayback $message): void
    {
        $this->messages[] = $message;
    }

    public function publishCancel(CancelAnnouncementPlayback $message): void
    {
        $this->cancels[] = $message;
    }

    public function publishStopRepeat(\App\Announcements\Application\Playback\StopAnnouncementRepeat $message): void
    {
    }

    public function publishStop(StopAnnouncementPlayback $message): void
    {
    }
}
