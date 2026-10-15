<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Application-layer publication point for every command sent to aeroflow-playback
 * (task 036): one port for the one boundary. Which queue a command travels on is
 * the implementation's concern, decided by the command's type.
 */
interface PlaybackCommandPublisherInterface
{
    public function publish(PlaybackCommand $command): void;
}
