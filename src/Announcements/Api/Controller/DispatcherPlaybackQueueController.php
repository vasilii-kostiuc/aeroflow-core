<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Application\ListPlaybackQueue\ListPlaybackQueueQuery;
use App\Announcements\Application\ListPlaybackQueue\PlaybackQueueResult;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dispatcher's playback queue screen (task 017) — heir of the legacy Status
 * window: playing now, waiting, recently finished. Read-only; row actions
 * (cancel pending, stop current) are the follow-up slices.
 */
#[Route('/dispatcher/playback-queue')]
final readonly class DispatcherPlaybackQueueController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new ListPlaybackQueueQuery(), PlaybackQueueResult::class));
    }
}
