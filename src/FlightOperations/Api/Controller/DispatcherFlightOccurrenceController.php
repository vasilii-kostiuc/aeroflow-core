<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\DispatcherFlightOccurrenceListRequest;
use App\FlightOperations\Application\Dispatcher\ListDispatcherFlightOccurrencesQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dispatcher/flight-occurrences')]
final class DispatcherFlightOccurrenceController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        DispatcherFlightOccurrenceListRequest $request,
    ): JsonResponse {
        return ApiResponse::success($this->bus->handleList(new ListDispatcherFlightOccurrencesQuery(
            operationalDate: $request->operationalDate,
            announcementType: $request->announcementType,
            direction: $request->direction,
            includeUnavailable: $request->includeUnavailable,
        ), \App\FlightOperations\Application\Dispatcher\DispatcherFlightOccurrenceResult::class));
    }
}
