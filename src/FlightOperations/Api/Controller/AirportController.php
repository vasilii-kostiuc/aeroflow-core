<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\AirportDetailsRequest;
use App\FlightOperations\Api\Request\AirportListRequest;
use App\FlightOperations\Api\Request\AirportUpdateRequest;
use App\FlightOperations\Application\AirportResult;
use App\FlightOperations\Application\ChangeAirportStatus\ChangeAirportStatusCommand;
use App\FlightOperations\Application\CreateAirport\CreateAirportCommand;
use App\FlightOperations\Application\GetAirport\GetAirportQuery;
use App\FlightOperations\Application\ListAirports\ListAirportsQuery;
use App\FlightOperations\Application\UpdateAirport\UpdateAirportCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\Shared\Application\Pagination\PaginatedResult;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/airports')]
#[OA\Tag(name: 'Airports')]
final class AirportController extends AbstractController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] AirportDetailsRequest $request): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(new CreateAirportCommand(
            $request->code,
            $request->name,
            $request->cityName,
            $request->countryCode,
        ), AirportResult::class));
    }

    #[Route('', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        AirportListRequest $request,
    ): JsonResponse {
        return ApiResponse::success($this->bus->handleAs(new ListAirportsQuery(
            $request->active,
            $request->search,
            $request->page,
            $request->limit,
        ), PaginatedResult::class));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetAirportQuery($id), AirportResult::class));
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] AirportUpdateRequest $request): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new UpdateAirportCommand(
            $id,
            $request->name,
            $request->cityName,
            $request->countryCode,
        ), AirportResult::class));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new ChangeAirportStatusCommand($id, true), AirportResult::class));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new ChangeAirportStatusCommand($id, false), AirportResult::class));
    }
}
