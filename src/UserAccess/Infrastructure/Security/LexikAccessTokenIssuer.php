<?php

namespace App\UserAccess\Infrastructure\Security;

use App\UserAccess\Application\Security\AccessTokenIssuerInterface;
use App\UserAccess\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LexikAccessTokenIssuer implements AccessTokenIssuerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtTokenManager,
        #[Autowire('%lexik_jwt_authentication.token_ttl%')]
        private int $tokenTtl,
    ) {
    }

    public function issue(User $user): string
    {
        return $this->jwtTokenManager->createFromPayload($user, [
            'uid' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    public function expiresIn(): int
    {
        return $this->tokenTtl;
    }
}
