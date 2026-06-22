<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\AnnouncementLanguagesRequest;
use App\Announcements\Api\Request\CreateAnnouncementRequest;
use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\CancelAnnouncement\CancelAnnouncementCommand;
use App\Announcements\Application\ChangeAnnouncementLanguages\ChangeAnnouncementLanguagesCommand;
use App\Announcements\Application\CreateAnnouncement\CreateAnnouncementCommand;
use App\Announcements\Application\GetAnnouncement\GetAnnouncementQuery;
use App\Announcements\Application\ListAnnouncements\ListAnnouncementsQuery;
use App\Shared\Api\Response\ApiResponse;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/announcements')]
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
            $request->checkInCounterStart,
            $request->checkInCounterEnd,
            $request->gateCode,
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

    #[Route('/{id}/languages', methods: ['PUT'])]
    public function languages(string $id, #[MapRequestPayload] AnnouncementLanguagesRequest $request): JsonResponse
    {
        return ApiResponse::success($this->one(new ChangeAnnouncementLanguagesCommand($id, $request->languages)));
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
