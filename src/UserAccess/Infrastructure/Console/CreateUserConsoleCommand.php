<?php

declare(strict_types=1);

namespace App\UserAccess\Infrastructure\Console;

use App\Shared\Application\Bus\ApplicationBus;
use App\UserAccess\Application\CreateUser\CreateUserCommand;
use App\UserAccess\Application\UserResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a user from the console (task 033) — the way to get the first
 * administrator on a fresh installation, since there is no self-registration.
 */
#[AsCommand(name: 'app:users:create', description: 'Create a user (e.g. the first administrator).')]
final class CreateUserConsoleCommand extends Command
{
    public function __construct(private readonly ApplicationBus $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail, used to sign in')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'admin, dispatcher or none', 'admin')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (asked interactively when omitted)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $password = $input->getOption('password');
        if (!is_string($password) || '' === $password) {
            $password = (string) $io->askHidden('Password (at least 8 characters)');
        }
        if (mb_strlen($password) < 8) {
            $io->error('The password must be at least 8 characters long.');

            return Command::FAILURE;
        }

        $role = (string) $input->getOption('role');
        $user = $this->bus->handleAs(
            new CreateUserCommand((string) $input->getArgument('email'), $password, 'none' === $role ? null : $role),
            UserResult::class,
        );

        $io->success(sprintf('User %s created with role: %s.', $user->email, $user->role ?? 'none'));

        return Command::SUCCESS;
    }
}
