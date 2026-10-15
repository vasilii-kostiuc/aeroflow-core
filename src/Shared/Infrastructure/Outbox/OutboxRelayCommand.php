<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Long-running outbox relay (task 035). Run exactly one per service: order within
 * a destination relies on a single sender. Services are reset after every pass,
 * as a Messenger worker does after every message, so the debug query log and the
 * like do not grow for the life of the process.
 */
#[AsCommand(name: 'app:outbox:relay', description: 'Deliver outbox messages to RabbitMQ.')]
final class OutboxRelayCommand extends Command
{
    private const int IDLE_MS = 200;
    private const int RETRY_MS = 1000;

    public function __construct(
        private readonly OutboxRelay $relay,
        #[Autowire(service: 'services_resetter')]
        private readonly ResetInterface $resetter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('once', null, InputOption::VALUE_NONE, 'Make a single pass and exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        do {
            ['sent' => $sent, 'failed' => $failed] = $this->relay->relay();
            $this->resetter->reset();
            if ($sent > 0) {
                $output->writeln(sprintf('Delivered %d outbox message(s).', $sent), OutputInterface::VERBOSITY_VERBOSE);
            }
            if (0 === $sent && !$input->getOption('once')) {
                usleep(($failed ? self::RETRY_MS : self::IDLE_MS) * 1000);
            }
        } while (!$input->getOption('once'));

        return Command::SUCCESS;
    }
}
