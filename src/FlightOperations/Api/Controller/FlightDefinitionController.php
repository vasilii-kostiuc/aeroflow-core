<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\FlightDefinitionDetailsRequest;
use App\FlightOperations\Api\Request\FlightDefinitionListRequest;
use App\FlightOperations\Application\ActivateFlightDefinition\ActivateFlightDefinitionCommand;
use App\FlightOperations\Application\CreateFlightDefinition\CreateFlightDefinitionCommand;
use App\FlightOperations\Application\DeactivateFlightDefinition\DeactivateFlightDefinitionCommand;
use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Application\GetFlightDefinition\GetFlightDefinitionQuery;
use App\FlightOperations\Application\ListFlightDefinitions\ListFlightDefinitionsQuery;
use App\FlightOperations\Application\UpdateFlightDefinition\UpdateFlightDefinitionCommand;
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

#[Route('/flight-definitions')]
#[OA\Tag(name: 'Flight definitions')]
final class FlightDefinitionController extends AbstractController
{
    public function __construct(
        private ApplicationBus $bus,
    ) {
    }

    #[Route('', name: 'app_flight_definition_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a flight definition',
        responses: [
            new OA\Response(response: 201, description: 'Flight definition created'),
            new OA\Response(response: 409, description: 'Duplicate flight definition'),
            new OA\Response(response: 422, description: 'Validation or domain error'),
        ],
    )]
    public function create(#[MapRequestPayload] FlightDefinitionDetailsRequest $request): JsonResponse
    {
        $result = $this->bus->handleAs(new CreateFlightDefinitionCommand(
            $request->flightNumber,
            $request->direction,
            $request->originAirportCode,
            $request->destinationAirportCode,
        ), FlightDefinitionResult::class);

        return ApiResponse::created($result, 'Flight definition created successfully');
    }

    #[Route('', name: 'app_flight_definition_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'List flight definitions',
        responses: [
            new OA\Response(response: 200, description: 'Paginated flight definitions'),
            new OA\Response(response: 422, description: 'Invalid filters or pagination'),
        ],
    )]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        FlightDefinitionListRequest $request,
    ): JsonResponse {
        $result = $this->bus->handleAs(new ListFlightDefinitionsQuery(
            active: $request->active,
            direction: $request->direction,
            search: $request->search,
            page: $request->page,
            limit: $request->limit,
        ), PaginatedResult::class);

        return ApiResponse::success($result);
    }

    #[Route('/{id}', name: 'app_flight_definition_get', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a flight definition',
        responses: [
            new OA\Response(response: 200, description: 'Flight definition'),
            new OA\Response(response: 404, description: 'Flight definition not found'),
            new OA\Response(response: 422, description: 'Invalid UUID'),
        ],
    )]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success(
            $this->bus->handleAs(new GetFlightDefinitionQuery($id), FlightDefinitionResult::class),
        );
    }

    #[Route('/{id}', name: 'app_flight_definition_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Update a flight definition',
        responses: [
            new OA\Response(response: 200, description: 'Flight definition updated'),
            new OA\Response(response: 404, description: 'Flight definition not found'),
            new OA\Response(response: 409, description: 'Duplicate flight definition'),
            new OA\Response(response: 422, description: 'Validation or domain error'),
        ],
    )]
    public function update(string $id, #[MapRequestPayload] FlightDefinitionDetailsRequest $request): JsonResponse
    {
        $result = $this->bus->handleAs(new UpdateFlightDefinitionCommand(
            $id,
            $request->flightNumber,
            $request->direction,
            $request->originAirportCode,
            $request->destinationAirportCode,
        ), FlightDefinitionResult::class);

        return ApiResponse::success($result, 'Flight definition updated successfully');
    }

    #[Route('/{id}/activate', name: 'app_flight_definition_activate', methods: ['POST'])]
    #[OA\Post(
        summary: 'Activate a flight definition',
        responses: [
            new OA\Response(response: 200, description: 'Flight definition activated'),
            new OA\Response(response: 404, description: 'Flight definition not found'),
            new OA\Response(response: 422, description: 'Invalid UUID'),
        ],
    )]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success(
            $this->bus->handleAs(new ActivateFlightDefinitionCommand($id), FlightDefinitionResult::class),
            'Flight definition activated successfully',
        );
    }

    #[Route('/{id}/deactivate', name: 'app_flight_definition_deactivate', methods: ['POST'])]
    #[OA\Post(
        summary: 'Deactivate a flight definition',
        responses: [
            new OA\Response(response: 200, description: 'Flight definition deactivated'),
            new OA\Response(response: 404, description: 'Flight definition not found'),
            new OA\Response(response: 422, description: 'Invalid UUID'),
        ],
    )]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success(
            $this->bus->handleAs(new DeactivateFlightDefinitionCommand($id), FlightDefinitionResult::class),
            'Flight definition deactivated successfully',
        );
    }
}
