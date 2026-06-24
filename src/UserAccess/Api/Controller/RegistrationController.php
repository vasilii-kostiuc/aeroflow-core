<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\Shared\Application\Bus\ApplicationBus;
use App\UserAccess\Api\Request\RegisterUserRequest;
use App\UserAccess\Application\RegisterUser\RegisterUserCommand;
use App\UserAccess\Application\RegisterUser\RegisterUserResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private ApplicationBus $bus,
    ) {
    }

    #[OA\Post(
        summary: 'Register a user',
        tags: ['User access'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'passwordConfirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'dispatcher@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'passwordConfirmation', type: 'string', format: 'password', example: 'password123'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            ref: new Model(type: RegisterUserResult::class),
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully'),
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
            new OA\Response(response: 422, description: 'Validation or domain error'),
        ],
    )]
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function index(#[MapRequestPayload] RegisterUserRequest $registerUserRequest): JsonResponse
    {
        $response = $this->bus->handleAs(new RegisterUserCommand(
            email: $registerUserRequest->email,
            password: $registerUserRequest->password,
        ), RegisterUserResult::class);

        return ApiResponse::created(
            message: 'User registered successfully',
            data: $response,
        );
    }
}
