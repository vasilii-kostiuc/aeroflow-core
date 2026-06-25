<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\OperationalResourceRequest;
use App\FlightOperations\Application\GateDirectory;
use App\FlightOperations\Application\OperationalResourceResult;
use App\Shared\Api\Response\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/gates')]
#[OA\Tag(name: 'Gates')]
final readonly class GateController
{
    public function __construct(private GateDirectory $directory)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: 200,
        description: 'Gates',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: OperationalResourceResult::class)),
                ),
            ],
            type: 'object',
        ),
    )]
    public function list(Request $request): JsonResponse
    {
        return ApiResponse::success($this->directory->list($this->activeFilter($request)));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Gate created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: OperationalResourceResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 409, description: 'Duplicate gate code')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function create(#[MapRequestPayload] OperationalResourceRequest $request): JsonResponse
    {
        return ApiResponse::created($this->directory->create(
            $request->code,
            $request->displayName,
            $request->sortOrder,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'Gate updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: OperationalResourceResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Gate not found')]
    #[OA\Response(response: 409, description: 'Duplicate gate code')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function update(string $id, #[MapRequestPayload] OperationalResourceRequest $request): JsonResponse
    {
        return ApiResponse::success($this->directory->update(
            $id,
            $request->code,
            $request->displayName,
            $request->sortOrder,
        ));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Gate activated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: OperationalResourceResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Gate not found')]
    #[OA\Response(response: 422, description: 'Invalid UUID')]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->changeStatus($id, true));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Gate deactivated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: OperationalResourceResult::class)),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 404, description: 'Gate not found')]
    #[OA\Response(response: 422, description: 'Invalid UUID')]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->changeStatus($id, false));
    }

    private function activeFilter(Request $request): ?bool
    {
        $value = $request->query->get('active');

        return $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
