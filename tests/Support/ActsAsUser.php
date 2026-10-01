<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\UserAccess\Application\Security\AccessTokenIssuerInterface;
use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Signs a functional test in as a fresh user with the given role (task 033:
 * there is no self-registration any more). The user is stored like any other,
 * so the per-request role lookup sees it; the test transaction rolls it back.
 */
trait ActsAsUser
{
    public const string TEST_PASSWORD = 'password123';

    protected function actAs(KernelBrowser $client, ?UserRole $role = UserRole::Admin): User
    {
        $container = static::getContainer();
        $user = User::register(
            'user-'.uniqid('', true).'@example.com',
            $container->get(PasswordHasherInterface::class)->hash(self::TEST_PASSWORD),
            $role,
        );
        $user->pullEvents();
        $container->get(UserRepositoryInterface::class)->save($user);

        $token = $container->get(AccessTokenIssuerInterface::class)->issue($user);
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        return $user;
    }
}
