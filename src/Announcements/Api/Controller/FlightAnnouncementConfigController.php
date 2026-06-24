<?php

declare(strict_types=1);

namespace App\Announcements\Api\Controller;

use App\Announcements\Api\Request\AnnouncementVariantRequest;
use App\Announcements\Api\Request\FlightAnnouncementConfigRequest;
use App\Announcements\Api\Request\FlightAnnouncementConfigSettingsRequest;
use App\Announcements\Application\AddAnnouncementVariant\AddAnnouncementVariantCommand;
use App\Announcements\Application\CreateFlightAnnouncementConfig\CreateFlightAnnouncementConfigCommand;
use App\Announcements\Application\DeleteAnnouncementVariant\DeleteAnnouncementVariantCommand;
use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Application\ListFlightAnnouncementConfigs\ListFlightAnnouncementConfigsQuery;
use App\Announcements\Application\UpdateAnnouncementVariant\UpdateAnnouncementVariantCommand;
use App\Announcements\Application\UpdateFlightAnnouncementConfig\UpdateFlightAnnouncementConfigCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/flight-definitions/{flightDefinitionId}/announcement-configs')]
#[OA\Tag(name: 'Flight announcement configs')]
final class FlightAnnouncementConfigController extends AbstractController
{
    public function __construct(
        private ApplicationBus $bus,
    ) {
    }

    #[Route('', name: 'app_flight_announcement_config_list', methods: ['GET'])]
    public function list(string $flightDefinitionId): JsonResponse
    {
        return ApiResponse::success($this->bus->handleList(
            new ListFlightAnnouncementConfigsQuery($flightDefinitionId),
            FlightAnnouncementConfigResult::class,
        ));
    }

    #[Route('', name: 'app_flight_announcement_config_create', methods: ['POST'])]
    public function create(
        string $flightDefinitionId,
        #[MapRequestPayload]
        FlightAnnouncementConfigRequest $request,
    ): JsonResponse {
        return ApiResponse::created(
            $this->bus->handleAs(new CreateFlightAnnouncementConfigCommand(
                $flightDefinitionId,
                $request->announcementType,
                $request->enabled,
                $request->repeatEveryMinutes,
            ), FlightAnnouncementConfigResult::class),
            'Flight announcement config created successfully',
        );
    }

    #[Route('/{configId}', name: 'app_flight_announcement_config_update', methods: ['PATCH'])]
    public function update(
        string $flightDefinitionId,
        string $configId,
        #[MapRequestPayload]
        FlightAnnouncementConfigSettingsRequest $request,
    ): JsonResponse {
        return ApiResponse::success(
            $this->bus->handleAs(new UpdateFlightAnnouncementConfigCommand(
                $flightDefinitionId,
                $configId,
                $request->enabled,
                $request->repeatEveryMinutes,
            ), FlightAnnouncementConfigResult::class),
            'Flight announcement config updated successfully',
        );
    }

    #[Route('/{configId}/variants', name: 'app_flight_announcement_variant_add', methods: ['POST'])]
    public function addVariant(
        string $flightDefinitionId,
        string $configId,
        #[MapRequestPayload]
        AnnouncementVariantRequest $request,
    ): JsonResponse {
        return ApiResponse::created(
            $this->bus->handleAs(new AddAnnouncementVariantCommand(
                $flightDefinitionId,
                $configId,
                $request->languageCode,
                $request->sortOrder,
                $request->segments,
                $request->enabled,
            ), FlightAnnouncementConfigResult::class),
            'Announcement variant added successfully',
        );
    }

    #[Route('/{configId}/variants/{variantId}', name: 'app_flight_announcement_variant_update', methods: ['PATCH'])]
    public function updateVariant(
        string $flightDefinitionId,
        string $configId,
        string $variantId,
        #[MapRequestPayload]
        AnnouncementVariantRequest $request,
    ): JsonResponse {
        return ApiResponse::success(
            $this->bus->handleAs(new UpdateAnnouncementVariantCommand(
                $flightDefinitionId,
                $configId,
                $variantId,
                $request->languageCode,
                $request->sortOrder,
                $request->segments,
                $request->enabled,
            ), FlightAnnouncementConfigResult::class),
            'Announcement variant updated successfully',
        );
    }

    #[Route('/{configId}/variants/{variantId}', name: 'app_flight_announcement_variant_delete', methods: ['DELETE'])]
    public function deleteVariant(
        string $flightDefinitionId,
        string $configId,
        string $variantId,
    ): JsonResponse {
        return ApiResponse::success(
            $this->bus->handleAs(
                new DeleteAnnouncementVariantCommand($flightDefinitionId, $configId, $variantId),
                FlightAnnouncementConfigResult::class,
            ),
            'Announcement variant deleted successfully',
        );
    }
}
