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
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/announcements')]
#[OA\Tag(name: 'Announcements')]
final class AnnouncementController
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAnnouncementRequest $request): JsonResponse
    {
        return ApiResponse::created($this->one(new CreateAnnouncementCommand(
            $request->type,
            $request->flightDefinitionId,
            $request->languages,
            $request->checkInCounterIds,
            $request->gateId,
        )));
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $result = $this->messageBus->dispatch(new ListAnnouncementsQuery())->last(HandledStamp::class)?->getResult();

        return ApiResponse::success($result);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success($this->one(new GetAnnouncementQuery($id)));
    }

    #[Route('/{id}/cancel', methods: ['POST'])]
    public function cancel(string $id): JsonResponse
    {
        return ApiResponse::success($this->one(new CancelAnnouncementCommand($id)));
    }

    private function one(object $message): AnnouncementResult
    {
        $result = $this->messageBus->dispatch($message)->last(HandledStamp::class)?->getResult();
        if (!$result instanceof AnnouncementResult) {
            throw new LogicException('Announcement handler did not return the expected result.');
        }

        return $result;
    }
}
