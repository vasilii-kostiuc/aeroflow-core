<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Announcements\Application\Playback\PlaybackCommand;
use App\Announcements\Application\Playback\PlaybackCommandPublisherInterface;

final class RecordingPlaybackCommandPublisher implements PlaybackCommandPublisherInterface
{
    /**
     * @var list<PlaybackCommand>
     */
    public array $commands = [];

    public function publish(PlaybackCommand $command): void
    {
        $this->commands[] = $command;
    }
}
