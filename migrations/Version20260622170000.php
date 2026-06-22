<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create announcements with ordered multilingual configuration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE announcement (id UUID NOT NULL, type VARCHAR(32) NOT NULL, flight_definition_id UUID NOT NULL, check_in_counter_start INT DEFAULT NULL, check_in_counter_end INT DEFAULT NULL, gate_code VARCHAR(16) DEFAULT NULL, language_codes JSON NOT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ANNOUNCEMENT_CREATED_AT ON announcement (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE announcement');
    }
}
