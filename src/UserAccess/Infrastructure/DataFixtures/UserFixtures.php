<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\DataFixtures;

use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture implements FixtureGroupInterface
{
    private const DEFAULT_PASSWORD = 'AeroFlow123!';

    /**
     * E-mail => role. The operator has no role on purpose: it shows the
     * «Нет доступа» screen of an account that exists but is not admitted.
     *
     * @var array<string, UserRole|null>
     */
    private const USERS = [
        'admin@aeroflow.local' => UserRole::Admin,
        'dispatcher@aeroflow.local' => UserRole::Dispatcher,
        'operator@aeroflow.local' => null,
    ];

    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $passwordHash = $this->passwordHasher->hash(self::DEFAULT_PASSWORD);

        foreach (self::USERS as $email => $role) {
            $user = $this->userRepository->findByEmail($email) ?? User::register($email, $passwordHash);
            $user->changeRole($role);
            $user->pullEvents();

            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['user-access'];
    }
}
