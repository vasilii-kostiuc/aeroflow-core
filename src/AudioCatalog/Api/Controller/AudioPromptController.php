<?php

declare(strict_types=1);

namespace App\AudioCatalog\Api\Controller;

use App\AudioCatalog\Api\Request\AudioPromptRequest;
use App\AudioCatalog\Application\AudioPromptManager;
use App\Shared\Api\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audio-prompts')]
#[OA\Tag(name: 'Audio prompts')]
final readonly class AudioPromptController
{
    public function __construct(private AudioPromptManager $manager)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Parameter(name: 'kind', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['check_in_counter_code', 'gate_code']))]
    #[OA\Parameter(name: 'value', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'languageCode', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    public function list(Request $request): JsonResponse
    {
        $active = $request->query->has('active')
            ? filter_var($request->query->get('active'), FILTER_VALIDATE_BOOL)
            : null;

        return ApiResponse::success($this->manager->list(
            $request->query->getString('kind') ?: null,
            $request->query->getString('value') ?: null,
            $request->query->getString('languageCode') ?: null,
            $active,
        ));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] AudioPromptRequest $r): JsonResponse
    {
        return ApiResponse::created($this->manager->create($r->kind, $r->value, $r->languageCode, $r->audioAssetId));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(string $id, #[MapRequestPayload] AudioPromptRequest $r): JsonResponse
    {
        return ApiResponse::success($this->manager->update($id, $r->kind, $r->value, $r->languageCode, $r->audioAssetId));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->manager->status($id, true));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->manager->status($id, false));
    }
}
