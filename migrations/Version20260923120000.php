<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the emergency announcement preset catalog and the mirrored emergency mode state (task 026).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE emergency_announcement_template (id UUID NOT NULL, name VARCHAR(255) NOT NULL, variants JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EMERGENCY_TEMPLATE_ACTIVE ON emergency_announcement_template (active)');
        $this->addSql('CREATE TABLE emergency_mode_record (id UUID NOT NULL, active BOOLEAN NOT NULL, occurred_at VARCHAR(64) NOT NULL, received_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE emergency_mode_record');
        $this->addSql('DROP TABLE emergency_announcement_template');
    }
}
