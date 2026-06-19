<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the reusable flight definition catalog.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE flight_definition (id UUID NOT NULL, flight_number VARCHAR(7) NOT NULL, direction VARCHAR(16) NOT NULL, origin_airport_code VARCHAR(3) NOT NULL, destination_airport_code VARCHAR(3) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FLIGHT_DEFINITION_BUSINESS_KEY ON flight_definition (flight_number, direction, origin_airport_code, destination_airport_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE flight_definition');
    }
}
