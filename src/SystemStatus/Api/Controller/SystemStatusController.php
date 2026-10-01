<?php

declare(strict_types=1);

namespace App\SystemStatus\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\SystemStatus\Application\GetSystemAlerts\GetSystemAlertsQuery;
use App\SystemStatus\Application\GetSystemStatus\GetSystemStatusQuery;
use App\SystemStatus\Application\SystemAlert;
use App\SystemStatus\Application\SystemStatusResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** «Состояние системы» for administrators and the alerts of the dispatcher panel (task 034). */
#[OA\Tag(name: 'System status')]
final readonly class SystemStatusController
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    #[Route('/admin/system-status', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Every component with its status',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: SystemStatusResult::class)),
            ],
            type: 'object',
        ),
    )]
    public function status(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleAs(new GetSystemStatusQuery(), SystemStatusResult::class));
    }

    #[Route('/dispatcher/system-alerts', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Problems that may keep announcements from sounding; empty when all is well',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: SystemAlert::class))),
            ],
            type: 'object',
        ),
    )]
    public function alerts(): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(new GetSystemAlertsQuery(), SystemAlert::class));
    }
}
