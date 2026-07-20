<?php

declare(strict_types=1);

namespace App\Announcements\Application\StopPlayback;

use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Shared\Application\Uuid\UuidParser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Operational dispatcher action; Announcement remains the immutable business fact. */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class StopPlaybackHandler
{
    public function __construct(private PlaybackRequestPublisherInterface $publisher, private UuidParser $uuidParser)
    {
    }

    public function __invoke(StopPlaybackCommand $command): void
    {
        // Reject a malformed id here: past this point the value crosses the service
        // boundary and playback would turn it into a poison message.
        $announcementId = $this->uuidParser->parse($command->announcementId)->toRfc4122();

        $this->publisher->publishStop(StopAnnouncementPlayback::forAnnouncement($announcementId));
    }
}
