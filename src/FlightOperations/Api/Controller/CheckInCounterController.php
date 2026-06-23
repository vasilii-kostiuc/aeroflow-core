<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\OperationalResourceRequest;
use App\FlightOperations\Application\CheckInCounterDirectory;
use App\Shared\Api\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/check-in-counters')]
#[OA\Tag(name: 'Check-in counters')]
final readonly class CheckInCounterController
{
    public function __construct(private CheckInCounterDirectory $directory)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    public function list(Request $request): JsonResponse
    {
        return ApiResponse::success($this->directory->list($this->activeFilter($request)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] OperationalResourceRequest $request): JsonResponse
    {
        return ApiResponse::created($this->directory->create(
            $request->code,
            $request->displayName,
            $request->sortOrder,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
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
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->changeStatus($id, true));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
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
