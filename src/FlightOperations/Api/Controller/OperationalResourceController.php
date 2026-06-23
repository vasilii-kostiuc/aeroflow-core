<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\OperationalResourceRequest;
use App\FlightOperations\Application\OperationalResourceDirectory;
use App\Shared\Api\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Operational resources')]
final readonly class OperationalResourceController
{
    public function __construct(private OperationalResourceDirectory $directory)
    {
    }

    #[Route('/admin/check-in-counters', methods: ['GET'])]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    public function counters(Request $request): JsonResponse
    {
        return ApiResponse::success($this->directory->list('check-in counter', $this->activeFilter($request)));
    }

    #[Route('/admin/check-in-counters', methods: ['POST'])]
    public function createCounter(#[MapRequestPayload] OperationalResourceRequest $r): JsonResponse
    {
        return ApiResponse::created($this->directory->create('check-in counter', $r->code, $r->displayName, $r->sortOrder));
    }

    #[Route('/admin/check-in-counters/{id}', methods: ['PATCH'])]
    public function updateCounter(string $id, #[MapRequestPayload] OperationalResourceRequest $r): JsonResponse
    {
        return ApiResponse::success($this->directory->update('check-in counter', $id, $r->code, $r->displayName, $r->sortOrder));
    }

    #[Route('/admin/check-in-counters/{id}/activate', methods: ['POST'])]
    public function activateCounter(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->status('check-in counter', $id, true));
    }

    #[Route('/admin/check-in-counters/{id}/deactivate', methods: ['POST'])]
    public function deactivateCounter(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->status('check-in counter', $id, false));
    }

    #[Route('/admin/gates', methods: ['GET'])]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    public function gates(Request $request): JsonResponse
    {
        return ApiResponse::success($this->directory->list('gate', $this->activeFilter($request)));
    }

    #[Route('/admin/gates', methods: ['POST'])]
    public function createGate(#[MapRequestPayload] OperationalResourceRequest $r): JsonResponse
    {
        return ApiResponse::created($this->directory->create('gate', $r->code, $r->displayName, $r->sortOrder));
    }

    #[Route('/admin/gates/{id}', methods: ['PATCH'])]
    public function updateGate(string $id, #[MapRequestPayload] OperationalResourceRequest $r): JsonResponse
    {
        return ApiResponse::success($this->directory->update('gate', $id, $r->code, $r->displayName, $r->sortOrder));
    }

    #[Route('/admin/gates/{id}/activate', methods: ['POST'])]
    public function activateGate(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->status('gate', $id, true));
    }

    #[Route('/admin/gates/{id}/deactivate', methods: ['POST'])]
    public function deactivateGate(string $id): JsonResponse
    {
        return ApiResponse::success($this->directory->status('gate', $id, false));
    }

    private function activeFilter(Request $request): ?bool
    {
        $value = $request->query->get('active');

        return $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
