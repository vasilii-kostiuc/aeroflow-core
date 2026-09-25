<?php

declare(strict_types=1);

namespace App\UserAccess\Application;

use App\UserAccess\Domain\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserResult', required: ['id', 'email', 'role'])]
final readonly class UserResult
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(format: 'email')]
        public string $email,
        #[OA\Property(description: 'null — no access', enum: ['admin', 'dispatcher'], nullable: true)]
        public ?string $role,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (string) $user->getId(),
            email: (string) $user->getEmail(),
            role: $user->getRole()?->code(),
        );
    }
}
