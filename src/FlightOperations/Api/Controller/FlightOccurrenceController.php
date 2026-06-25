<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\FlightOccurrenceCreateRequest;
use App\FlightOperations\Api\Request\FlightOccurrenceListRequest;
use App\FlightOperations\Application\CreateFlightOccurrence\CreateFlightOccurrenceCommand;
use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Application\GetFlightOccurrence\GetFlightOccurrenceQuery;
use App\FlightOperations\Application\ListFlightOccurrences\ListFlightOccurrencesQuery;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\Shared\Application\Pagination\PaginatedResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/flight-occurrences')]
#[OA\Tag(name: 'Flight occurrences')]
final class FlightOccurrenceController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Flight occurrence created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: FlightOccurrenceResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Flight definition not found')]
    #[OA\Response(response: 409, description: 'Duplicate flight occurrence')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function create(#[MapRequestPayload] FlightOccurrenceCreateRequest $request): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(new CreateFlightOccurrenceCommand(
            flightDefinitionId: $request->flightDefinitionId,
            operationalDate: $request->operationalDate,
            sequenceNumber: $request->sequenceNumber,
            source: $request->source,
        ), FlightOccurrenceResult::class));
    }

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Flight occurrences',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: PaginatedResult::class)),
            ],
            type: 'object',
        ),
    )]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        FlightOccurrenceListRequest $request,
    ): JsonResponse {
        return ApiResponse::success($this->bus->handleAs(new ListFlightOccurrencesQuery(
            operationalDate: $request->operationalDate,
            flightDefinitionId: $request->flightDefinitionId,
            direction: $request->direction,
            status: $request->status,
            source: $request->source,
            page: $request->page,
            limit: $request->limit,
        ), PaginatedResult::class));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Flight occurrence',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: FlightOccurrenceResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Flight occurrence not found')]
    #[OA\Response(response: 422, description: 'Invalid UUID')]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetFlightOccurrenceQuery($id), FlightOccurrenceResult::class));
    }
}
