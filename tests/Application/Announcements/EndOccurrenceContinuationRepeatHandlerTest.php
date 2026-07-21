<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EndOccurrenceContinuationRepeat\EndOccurrenceContinuationRepeatCommand;
use App\Announcements\Application\EndOccurrenceContinuationRepeat\EndOccurrenceContinuationRepeatHandler;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementRepeatEnded;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EndOccurrenceContinuationRepeatHandlerTest extends TestCase
{
    public function testEndsActiveSeriesAndPublishesEvent(): void
    {
        $occurrenceId = Uuid::v7()->toRfc4122();
        $continuation = $this->continuation($occurrenceId);
        $continuation->pullEvents();

        $repo = $this->createMock(AnnouncementRepositoryInterface::class);
        $repo->method('findActiveContinuationByOccurrenceId')->willReturn($continuation);
        $repo->expects(self::once())->method('save')->with($continuation);

        $events = new RecordingDomainEventPublisher();

        (new EndOccurrenceContinuationRepeatHandler($repo, $events))(
            new EndOccurrenceContinuationRepeatCommand($occurrenceId),
        );

        self::assertNotNull($continuation->getRepeatEndedAt());
        self::assertCount(1, $events->published);
        self::assertInstanceOf(AnnouncementRepeatEnded::class, $events->published[0]);
    }

    public function testNoActiveSeriesIsNoOp(): void
    {
        $repo = $this->createMock(AnnouncementRepositoryInterface::class);
        $repo->method('findActiveContinuationByOccurrenceId')->willReturn(null);
        $repo->expects(self::never())->method('save');

        $events = new RecordingDomainEventPublisher();

        (new EndOccurrenceContinuationRepeatHandler($repo, $events))(
            new EndOccurrenceContinuationRepeatCommand(Uuid::v7()->toRfc4122()),
        );

        self::assertSame([], $events->published);
    }

    public function testMalformedOccurrenceIdIsNoOp(): void
    {
        $repo = $this->createMock(AnnouncementRepositoryInterface::class);
        $repo->expects(self::never())->method('findActiveContinuationByOccurrenceId');

        (new EndOccurrenceContinuationRepeatHandler($repo, new RecordingDomainEventPublisher()))(
            new EndOccurrenceContinuationRepeatCommand('not-a-uuid'),
        );
    }

    private function continuation(string $occurrenceId): Announcement
    {
        return Announcement::createPrepared(
            AnnouncementType::CheckInContinuation,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [['id' => Uuid::v7()->toRfc4122(), 'code' => '3']],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => []]],
            $occurrenceId,
            5,
        );
    }
}

final class RecordingDomainEventPublisher implements DomainEventPublisher
{
    /** @var list<object> */
    public array $published = [];

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
        }
    }
}
