<?php

declare(strict_types=1);

namespace App\UserAccess\Application\SetUserPassword;

final readonly class SetUserPasswordCommand
{
    public function __construct(
        public string $userId,
        public string $password,
    ) {
    }
}
