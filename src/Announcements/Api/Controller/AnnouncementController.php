<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\CreateAnnouncementRequest;
use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\CancelAnnouncement\CancelAnnouncementCommand;
use App\Announcements\Application\CreateAnnouncement\CreateAnnouncementCommand;
use App\Announcements\Application\GetAnnouncement\GetAnnouncementQuery;
use App\Announcements\Application\ListAnnouncements\ListAnnouncementsQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/announcements')]
#[OA\Tag(name: 'Announcements')]
final class AnnouncementController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAnnouncementRequest $request): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(new CreateAnnouncementCommand(
            $request->type,
            $request->flightDefinitionId,
            $request->languages,
            $request->checkInCounterIds,
            $request->gateId,
        ), AnnouncementResult::class));
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListAnnouncementsQuery(), AnnouncementResult::class));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetAnnouncementQuery($id), AnnouncementResult::class));
    }

    #[Route('/{id}/cancel', methods: ['POST'])]
    public function cancel(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new CancelAnnouncementCommand($id), AnnouncementResult::class));
    }
}
