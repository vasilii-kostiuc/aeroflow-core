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
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/flight-definitions/{flightDefinitionId}/announcement-configs')]
#[OA\Tag(name: 'Flight announcement configs')]
final class FlightAnnouncementConfigController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', name: 'app_flight_announcement_config_list', methods: ['GET'])]
    public function list(string $flightDefinitionId): JsonResponse
    {
        return ApiResponse::success($this->rawResult(new ListFlightAnnouncementConfigsQuery($flightDefinitionId)));
    }

    #[Route('', name: 'app_flight_announcement_config_create', methods: ['POST'])]
    public function create(
        string $flightDefinitionId,
        #[MapRequestPayload]
        FlightAnnouncementConfigRequest $request,
    ): JsonResponse {
        return ApiResponse::created(
            $this->result(new CreateFlightAnnouncementConfigCommand(
                $flightDefinitionId,
                $request->announcementType,
                $request->enabled,
                $request->repeatEveryMinutes,
            )),
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
            $this->result(new UpdateFlightAnnouncementConfigCommand(
                $flightDefinitionId,
                $configId,
                $request->enabled,
                $request->repeatEveryMinutes,
            )),
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
            $this->result(new AddAnnouncementVariantCommand(
                $flightDefinitionId,
                $configId,
                $request->languageCode,
                $request->sortOrder,
                $request->segments,
                $request->enabled,
            )),
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
            $this->result(new UpdateAnnouncementVariantCommand(
                $flightDefinitionId,
                $configId,
                $variantId,
                $request->languageCode,
                $request->sortOrder,
                $request->segments,
                $request->enabled,
            )),
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
            $this->result(new DeleteAnnouncementVariantCommand($flightDefinitionId, $configId, $variantId)),
            'Announcement variant deleted successfully',
        );
    }

    private function result(object $message): FlightAnnouncementConfigResult
    {
        $result = $this->rawResult($message);

        if (!$result instanceof FlightAnnouncementConfigResult) {
            throw new LogicException('Flight announcement config handler did not return the expected result.');
        }

        return $result;
    }

    private function rawResult(object $message): mixed
    {
        return $this->messageBus->dispatch($message)->last(HandledStamp::class)?->getResult();
    }
}
