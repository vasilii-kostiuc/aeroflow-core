<?php

declare(strict_types=1);

namespace App\UserAccess\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** `role: null` (or missing) removes access. */
#[OA\Schema(schema: 'ChangeUserRoleRequest')]
final readonly class ChangeUserRoleRequest
{
    public function __construct(
        #[OA\Property(enum: ['admin', 'dispatcher'], nullable: true)]
        #[Assert\Choice(choices: ['admin', 'dispatcher'])]
        public ?string $role = null,
    ) {
    }
}
