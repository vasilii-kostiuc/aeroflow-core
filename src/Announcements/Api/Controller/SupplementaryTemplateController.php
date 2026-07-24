<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\SupplementaryTemplateRequest;
use App\Announcements\Application\ChangeSupplementaryTemplateStatus\ChangeSupplementaryTemplateStatusCommand;
use App\Announcements\Application\CreateSupplementaryTemplate\CreateSupplementaryTemplateCommand;
use App\Announcements\Application\ListSupplementaryTemplates\ListSupplementaryTemplatesQuery;
use App\Announcements\Application\SupplementaryTemplateResult;
use App\Announcements\Application\UpdateSupplementaryTemplate\UpdateSupplementaryTemplateCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin catalog of supplementary announcement presets (task 024) — the heir of the
 * legacy САО "Дополнительно" set. Each preset carries pre-recorded/generated audio
 * per language; the dispatcher launches one by id.
 */
#[Route('/admin/supplementary-announcement-templates')]
#[OA\Tag(name: 'Supplementary announcement templates')]
final readonly class SupplementaryTemplateController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: 200,
        description: 'Supplementary announcement templates',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: SupplementaryTemplateResult::class))),
            ],
            type: 'object',
        ),
    )]
    public function list(Request $request): JsonResponse
    {
        $active = $request->query->has('active')
            ? filter_var($request->query->get('active'), FILTER_VALIDATE_BOOL)
            : null;

        return ApiResponse::success($this->bus->handleList(
            new ListSupplementaryTemplatesQuery($active),
            SupplementaryTemplateResult::class,
        ));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Supplementary announcement template created')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function create(#[MapRequestPayload] SupplementaryTemplateRequest $r): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(
            new CreateSupplementaryTemplateCommand($r->name, $r->variants),
            SupplementaryTemplateResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'Supplementary announcement template updated')]
    #[OA\Response(response: 404, description: 'Template not found')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function update(string $id, #[MapRequestPayload] SupplementaryTemplateRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new UpdateSupplementaryTemplateCommand($id, $r->name, $r->variants),
            SupplementaryTemplateResult::class,
        ));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Supplementary announcement template activated')]
    #[OA\Response(response: 404, description: 'Template not found')]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeSupplementaryTemplateStatusCommand($id, true),
            SupplementaryTemplateResult::class,
        ));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Supplementary announcement template deactivated')]
    #[OA\Response(response: 404, description: 'Template not found')]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeSupplementaryTemplateStatusCommand($id, false),
            SupplementaryTemplateResult::class,
        ));
    }
}
