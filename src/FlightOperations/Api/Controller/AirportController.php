<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Controller;

use App\FlightOperations\Api\Request\AirportDetailsRequest;
use App\FlightOperations\Api\Request\AirportListRequest;
use App\FlightOperations\Api\Request\AirportUpdateRequest;
use App\FlightOperations\Application\AirportResult;
use App\FlightOperations\Application\ChangeAirportStatus\ChangeAirportStatusCommand;
use App\FlightOperations\Application\CreateAirport\CreateAirportCommand;
use App\FlightOperations\Application\GetAirport\GetAirportQuery;
use App\FlightOperations\Application\ListAirports\ListAirportsQuery;
use App\FlightOperations\Application\UpdateAirport\UpdateAirportCommand;
use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Pagination\PaginatedResult;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/airports')]
#[OA\Tag(name: 'Airports')]
final class AirportController extends AbstractController
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] AirportDetailsRequest $request): JsonResponse
    {
        return ApiResponse::created($this->result(new CreateAirportCommand(
            $request->code,
            $request->name,
            $request->cityName,
            $request->countryCode,
        )));
    }

    #[Route('', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        AirportListRequest $request,
    ): JsonResponse {
        return ApiResponse::success($this->result(new ListAirportsQuery(
            $request->active,
            $request->search,
            $request->page,
            $request->limit,
        )));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return ApiResponse::success($this->result(new GetAirportQuery($id)));
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] AirportUpdateRequest $request): JsonResponse
    {
        return ApiResponse::success($this->result(new UpdateAirportCommand(
            $id,
            $request->name,
            $request->cityName,
            $request->countryCode,
        )));
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        return ApiResponse::success($this->result(new ChangeAirportStatusCommand($id, true)));
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    public function deactivate(string $id): JsonResponse
    {
        return ApiResponse::success($this->result(new ChangeAirportStatusCommand($id, false)));
    }

    /**
     * @return AirportResult|PaginatedResult<AirportResult>
     */
    private function result(object $message): AirportResult|PaginatedResult
    {
        $result = $this->messageBus->dispatch($message)->last(HandledStamp::class)?->getResult();

        if (!$result instanceof AirportResult && !$result instanceof PaginatedResult) {
            throw new LogicException('Airport handler did not return the expected result.');
        }

        return $result;
    }
}
