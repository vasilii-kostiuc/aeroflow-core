<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\EmergencyTemplateRequest;
use App\Announcements\Application\ChangeEmergencyTemplateStatus\ChangeEmergencyTemplateStatusCommand;
use App\Announcements\Application\CreateEmergencyTemplate\CreateEmergencyTemplateCommand;
use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Application\ListEmergencyTemplates\ListEmergencyTemplatesQuery;
use App\Announcements\Application\UpdateEmergencyTemplate\UpdateEmergencyTemplateCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin catalog of emergency announcement presets (task 026) — the heir of the
 * legacy САО "Экстренные" set. Same shape as the supplementary catalog; what differs
 * is the launch: an emergency preset also activates emergency mode.
 */
#[Route('/admin/emergency-announcement-templates')]
#[OA\Tag(name: 'Emergency announcement templates')]
final readonly class EmergencyTemplateController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Parameter(name: 'active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: 200,
        description: 'Emergency announcement templates',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: EmergencyTemplateResult::class))),
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
            new ListEmergencyTemplatesQuery($active),
            EmergencyTemplateResult::class,
        ));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Emergency announcement template created')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function create(#[MapRequestPayload] EmergencyTemplateRequest $r): JsonResponse
    {
        return ApiResponse::created($this->bus->handleAs(
            new CreateEmergencyTemplateCommand($r->name, $r->variants),
            EmergencyTemplateResult::class,
        ));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'Emergency announcement template updated')]
    #[OA\Response(response: 404, description: 'Template not found')]
    #[OA\Response(response: 422, description: 'Validation or domain error')]
    public function update(string $id, #[MapRequestPayload] EmergencyTemplateRequest $r): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new UpdateEmergencyTemplateCommand($id, $r->name, $r->variants),
            EmergencyTemplateResult::class,
        ));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Emergency announcement template activated')]
    #[OA\Response(response: 404, description: 'Template not found')]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeEmergencyTemplateStatusCommand($id, true),
            EmergencyTemplateResult::class,
        ));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Emergency announcement template deactivated')]
    #[OA\Response(response: 404, description: 'Template not found')]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(
            new ChangeEmergencyTemplateStatusCommand($id, false),
            EmergencyTemplateResult::class,
        ));
    }
}
