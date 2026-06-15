<?php

namespace App\UserAccess\Api\Controller;

use App\Shared\Api\Response\ApiResponse;
use App\UserAccess\Api\Request\RegisterUserRequest;
use App\UserAccess\Application\RegisterUser\RegisterUserCommand;
use App\UserAccess\Application\RegisterUser\RegisterUserResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use App\UserAccess\Domain\Entity\User;
use Symfony\Component\Messenger\Attribute\HandledStamp;
use Symfony\Component\Messenger\MessageBusInterface;


final class RegistrationController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function index(#[MapRequestPayload] RegisterUserRequest $registerUserRequest): JsonResponse
    {
        $envelope = $this->messageBus->dispatch(new RegisterUserCommand(
        email: $registerUserRequest->email,
        password: $registerUserRequest->password,
    ));

    /** @var User $user */
    $user = $envelope->last(HandledStamp::class)?->getResult();

    return ApiResponse::created(
        message: 'User registered successfully',
        data: new RegisterUserResponse(
            id: $user->getId(),
            email: $user->getEmail(),
        ),
    );
    }
}
