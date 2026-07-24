<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create supplementary announcement template preset catalog (task 024).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE supplementary_announcement_template (id UUID NOT NULL, name VARCHAR(255) NOT NULL, variants JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_SUPPLEMENTARY_TEMPLATE_ACTIVE ON supplementary_announcement_template (active)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE supplementary_announcement_template');
    }
}
