<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\DataFixtures;

use App\UserAccess\Application\Security\PasswordHasherInterface;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture implements FixtureGroupInterface
{
    private const DEFAULT_PASSWORD = 'AeroFlow123!';

    private const EMAILS = [
        'admin@aeroflow.local',
        'dispatcher@aeroflow.local',
        'operator@aeroflow.local',
    ];

    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $passwordHash = $this->passwordHasher->hash(self::DEFAULT_PASSWORD);

        foreach (self::EMAILS as $email) {
            if ($this->userRepository->findByEmail($email) !== null) {
                continue;
            }

            $user = User::register($email, $passwordHash);
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
