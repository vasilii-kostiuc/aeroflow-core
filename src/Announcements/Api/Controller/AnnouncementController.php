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
use Nelmio\ApiDocBundle\Attribute\Model;
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
    #[OA\Response(
        response: 201,
        description: 'Announcement created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AnnouncementResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Flight definition or configuration not found')]
    #[OA\Response(response: 422, description: 'Validation, inactive resource, missing prompt, or template resolution error')]
    public function create(#[MapRequestPayload] CreateAnnouncementRequest $request): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(new CreateAnnouncementCommand(
            $request->type,
            $request->languages,
            $request->flightDefinitionId,
            $request->flightOccurrenceId,
            $request->checkInCounterIds,
            $request->gateId,
        ), AnnouncementResult::class));
    }

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Announcements',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: AnnouncementResult::class)),
                ),
            ],
            type: 'object',
        ),
    )]
    public function list(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new ListAnnouncementsQuery(), AnnouncementResult::class));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Announcement',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AnnouncementResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Announcement not found')]
    #[OA\Response(response: 422, description: 'Invalid UUID')]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetAnnouncementQuery($id), AnnouncementResult::class));
    }

    #[Route('/{id}/cancel', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Announcement cancelled',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: AnnouncementResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Announcement not found')]
    #[OA\Response(response: 422, description: 'Invalid UUID or announcement cannot be cancelled')]
    public function cancel(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new CancelAnnouncementCommand($id), AnnouncementResult::class));
    }
}
