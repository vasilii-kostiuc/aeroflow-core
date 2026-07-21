<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the continuation repeat interval and its end time on announcements (task 020).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement ADD repeat_every_minutes INT DEFAULT NULL');
        $this->addSql('ALTER TABLE announcement ADD repeat_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement DROP repeat_every_minutes');
        $this->addSql('ALTER TABLE announcement DROP repeat_ended_at');
    }
}
