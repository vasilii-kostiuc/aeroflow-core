<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Last heartbeat of each core and playback worker, for the system status screen (task 034).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE system_heartbeat (worker_key VARCHAR(255) NOT NULL, service VARCHAR(32) NOT NULL, transports JSON NOT NULL, last_seen_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, details JSON NOT NULL, PRIMARY KEY (worker_key))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE system_heartbeat');
    }
}
