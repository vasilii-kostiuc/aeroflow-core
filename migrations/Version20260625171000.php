<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625171000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add flight occurrences and optional occurrence link on announcements.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE flight_occurrence (id UUID NOT NULL, flight_definition_id UUID NOT NULL, source VARCHAR(16) NOT NULL, direction VARCHAR(16) NOT NULL, operational_date DATE NOT NULL, sequence_number INT NOT NULL, flight_number_snapshot VARCHAR(16) NOT NULL, origin_airport_code_snapshot VARCHAR(3) NOT NULL, destination_airport_code_snapshot VARCHAR(3) NOT NULL, status VARCHAR(32) NOT NULL, check_in_counters JSON NOT NULL, gate JSON DEFAULT NULL, last_announcement_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FLIGHT_OCCURRENCE_BUSINESS_KEY ON flight_occurrence (flight_definition_id, operational_date, source, sequence_number)');
        $this->addSql('CREATE INDEX IDX_FLIGHT_OCCURRENCE_OPERATIONAL_DATE ON flight_occurrence (operational_date)');
        $this->addSql('CREATE INDEX IDX_FLIGHT_OCCURRENCE_STATUS ON flight_occurrence (status)');
        $this->addSql('ALTER TABLE announcement ADD flight_occurrence_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_ANNOUNCEMENT_FLIGHT_OCCURRENCE_ID ON announcement (flight_occurrence_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_ANNOUNCEMENT_FLIGHT_OCCURRENCE_ID');
        $this->addSql('ALTER TABLE announcement DROP flight_occurrence_id');
        $this->addSql('DROP TABLE flight_occurrence');
    }
}
