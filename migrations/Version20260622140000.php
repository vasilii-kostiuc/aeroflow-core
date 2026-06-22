<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the airport directory.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE airport (id UUID NOT NULL, code VARCHAR(3) NOT NULL, name VARCHAR(160) NOT NULL, city_name VARCHAR(120) NOT NULL, country_code VARCHAR(2) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AIRPORT_CODE ON airport (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE airport');
    }
}
