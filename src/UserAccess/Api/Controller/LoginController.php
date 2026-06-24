<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\UserAccess\Api\Request\LoginUserRequest;
use App\UserAccess\Application\LoginUser\LoginUserCommand;
use App\UserAccess\Application\LoginUser\LoginUserResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(
        private ApplicationBus $bus,
    ) {
    }

    #[OA\Post(
        summary: 'Login a user',
        tags: ['User access'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'dispatcher@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User logged in successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            ref: new Model(type: LoginUserResult::class),
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'User logged in successfully'),
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
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function index(#[MapRequestPayload] LoginUserRequest $loginUserRequest): JsonResponse
    {
        $response = $this->bus->handleAs(new LoginUserCommand(
            email: $loginUserRequest->email,
            password: $loginUserRequest->password,
        ), LoginUserResult::class);

        return ApiResponse::success(
            message: 'User logged in successfully',
            data: $response,
        );
    }
}
