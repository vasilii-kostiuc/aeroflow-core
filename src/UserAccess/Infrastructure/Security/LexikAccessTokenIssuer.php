<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Security;

use App\UserAccess\Application\Security\AccessTokenIssuerInterface;
use App\UserAccess\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use LogicException;
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
        $email = $user->getEmail();
        if ($email === null || $email === '') {
            throw new LogicException('Cannot issue access token for user without email.');
        }

        $jwtUser = new JwtUser($email, $user->getRoles());

        return $this->jwtTokenManager->createFromPayload($jwtUser, [
            'uid' => (string) $user->getId(),
            'email' => $email,
            'roles' => $user->getRoles(),
        ]);
    }

    public function expiresIn(): int
    {
        return $this->tokenTtl;
    }
}
