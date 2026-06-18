<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess\Support;

use App\UserAccess\Application\Security\RefreshTokenGeneratorInterface;

final class QueueRefreshTokenGenerator implements RefreshTokenGeneratorInterface
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        private array $tokens = ['refresh-token'],
    ) {
    }

    public function generate(): string
    {
        return array_shift($this->tokens) ?? 'refresh-token';
    }
}
