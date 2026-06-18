<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserAccess\Infrastructure\Persistance\Doctrine;

use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    public function testSavesAndFindsUserByEmailAndId(): void
    {
        self::bootKernel();

        $repository = self::getContainer()->get(UserRepositoryInterface::class);
        self::assertInstanceOf(UserRepositoryInterface::class, $repository);

        $user = User::register('repository-'.uniqid('', true).'@example.com', 'hashed-password');
        $user->pullEvents();

        $repository->save($user);

        self::assertSame($user, $repository->findByEmail((string) $user->getEmail()));
        self::assertSame($user, $repository->findById($user->getId()));
    }
}
