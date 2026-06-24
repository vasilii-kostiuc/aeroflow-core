<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\UserAccess\Api\Request\LogoutUserRequest;
use App\UserAccess\Api\Request\RefreshTokenRequest;
use App\UserAccess\Application\LogoutUser\LogoutUserCommand;
use App\UserAccess\Application\RefreshToken\RefreshTokenCommand;
use App\UserAccess\Application\RefreshToken\RefreshTokenResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class TokenController extends AbstractController
{
    public function __construct(
        private ApplicationBus $bus,
    ) {
    }

    #[OA\Post(
        summary: 'Refresh access token',
        tags: ['User access'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token pair refreshed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: new Model(type: RefreshTokenResult::class)),
                        new OA\Property(property: 'message', type: 'string', example: 'Token refreshed successfully'),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'message', type: 'string'),
                                    new OA\Property(property: 'field', type: 'string', nullable: true),
                                    new OA\Property(property: 'code', type: 'string', nullable: true),
                                ],
                                type: 'object',
                            ),
                            example: [],
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Invalid refresh token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    #[Route('/token/refresh', name: 'app_token_refresh', methods: ['POST'])]
    public function refresh(#[MapRequestPayload] RefreshTokenRequest $request): JsonResponse
    {
        $response = $this->bus->handleAs(new RefreshTokenCommand($request->refreshToken), RefreshTokenResult::class);

        return ApiResponse::success(
            data: $response,
            message: 'Token refreshed successfully',
        );
    }

    #[OA\Post(
        summary: 'Logout user',
        tags: ['User access'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(response: 204, description: 'User logged out successfully'),
            new OA\Response(response: 401, description: 'Invalid refresh token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(#[MapRequestPayload] LogoutUserRequest $request): JsonResponse
    {
        $this->bus->dispatch(new LogoutUserCommand($request->refreshToken));

        return ApiResponse::noContent();
    }
}
