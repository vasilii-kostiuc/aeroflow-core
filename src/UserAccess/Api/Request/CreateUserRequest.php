<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(schema: 'CreateUserRequest', required: ['email', 'password'])]
final readonly class CreateUserRequest
{
    public function __construct(
        #[OA\Property(format: 'email', example: 'dispatcher@airport.md')]
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[OA\Property(format: 'password', minLength: 8)]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
        #[OA\Property(enum: ['admin', 'dispatcher'], nullable: true)]
        #[Assert\Choice(choices: ['admin', 'dispatcher'])]
        public ?string $role = null,
    ) {
    }
}
