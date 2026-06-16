<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

new Dotenv()->bootEnv(dirname(__DIR__, 2).'/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$doctrine = $container->get('doctrine');

if (!$doctrine instanceof ManagerRegistry) {
    throw new RuntimeException('Doctrine manager registry service is not available.');
}

return $doctrine->getManager();
