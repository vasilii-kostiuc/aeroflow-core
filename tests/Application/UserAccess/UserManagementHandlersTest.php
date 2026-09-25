<?php

declare(strict_types=1);

namespace App\Tests\Application\UserAccess;

use App\Tests\Application\UserAccess\Support\FakePasswordHasher;
use App\Tests\Application\UserAccess\Support\InMemoryRefreshTokenRepository;
use App\Tests\Application\UserAccess\Support\InMemoryUserRepository;
use App\Tests\Support\RecordingEventPublisher;
use App\UserAccess\Application\ChangeUserRole\ChangeUserRoleCommand;
use App\UserAccess\Application\ChangeUserRole\ChangeUserRoleHandler;
use App\UserAccess\Application\CreateUser\CreateUserCommand;
use App\UserAccess\Application\CreateUser\CreateUserHandler;
use App\UserAccess\Application\SetUserPassword\SetUserPasswordCommand;
use App\UserAccess\Application\SetUserPassword\SetUserPasswordHandler;
use App\UserAccess\Application\Support\UserLoader;
use App\UserAccess\Application\UserResult;
use App\UserAccess\Domain\Entity\RefreshToken;
use App\UserAccess\Domain\Entity\User;
use App\UserAccess\Domain\Enum\UserRole;
use App\UserAccess\Domain\Event\UserRoleChanged;
use App\UserAccess\Domain\Exception\LastAdministratorException;
use App\UserAccess\Domain\Exception\UserAlreadyExistsException;
use App\UserAccess\Domain\Exception\UserNotFoundException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserManagementHandlersTest extends TestCase
{
    private InMemoryUserRepository $users;
    private RecordingEventPublisher $events;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->events = new RecordingEventPublisher();
    }

    public function testCreatesUserWithRoleAndRejectsDuplicateEmail(): void
    {
        $handler = new CreateUserHandler($this->users, new FakePasswordHasher(), $this->events);

        $result = $handler(new CreateUserCommand(' d@example.com ', 'password123', 'dispatcher'));

        self::assertSame('d@example.com', $result->email);
        self::assertSame('dispatcher', $result->role);
        self::assertNotSame('password123', $this->users->findByEmail('d@example.com')?->getPassword());

        $this->expectException(UserAlreadyExistsException::class);
        $handler(new CreateUserCommand('d@example.com', 'password123', null));
    }

    public function testTheLastAdministratorCannotLoseTheRole(): void
    {
        $admin = $this->user('a@example.com', UserRole::Admin);

        $this->expectException(LastAdministratorException::class);
        $this->changeRole($admin, 'dispatcher');
    }

    public function testAnAdministratorCanStepDownWhenAnotherRemains(): void
    {
        $admin = $this->user('a@example.com', UserRole::Admin);
        $this->user('b@example.com', UserRole::Admin);

        $result = $this->changeRole($admin, null);

        self::assertNull($result->role);
        self::assertInstanceOf(UserRoleChanged::class, $this->events->messages[0]);
    }

    public function testUnchangedRolePublishesNothing(): void
    {
        $dispatcher = $this->user('d@example.com', UserRole::Dispatcher);

        $this->changeRole($dispatcher, 'dispatcher');

        self::assertSame([], $this->events->messages);
    }

    public function testUnknownUserIsNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        new ChangeUserRoleHandler($this->users, new UserLoader($this->users), $this->events)(
            new ChangeUserRoleCommand(Uuid::v7()->toRfc4122(), 'admin'),
        );
    }

    public function testSettingAPasswordRevokesRefreshTokens(): void
    {
        $user = $this->user('d@example.com', UserRole::Dispatcher);
        $tokens = new InMemoryRefreshTokenRepository();
        $tokens->saveAll([
            RefreshToken::issue($user, 'hash-1', new DateTimeImmutable('+1 day')),
            RefreshToken::issue($user, 'hash-2', new DateTimeImmutable('+1 day')),
        ]);

        new SetUserPasswordHandler($this->users, $tokens, new FakePasswordHasher(), new UserLoader($this->users), $this->events)(
            new SetUserPasswordCommand((string) $user->getId(), 'new-password'),
        );

        foreach ($tokens->all() as $token) {
            self::assertNotNull($token->getRevokedAt());
        }
        self::assertNotSame('h', $user->getPassword());
    }

    private function user(string $email, ?UserRole $role): User
    {
        $user = User::register($email, 'h', $role);
        $user->pullEvents();
        $this->users->save($user);

        return $user;
    }

    private function changeRole(User $user, ?string $role): UserResult
    {
        return new ChangeUserRoleHandler($this->users, new UserLoader($this->users), $this->events)(
            new ChangeUserRoleCommand((string) $user->getId(), $role),
        );
    }
}
