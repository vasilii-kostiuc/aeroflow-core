<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create flight announcement configs and text/audio variants.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audio_asset (id UUID NOT NULL, name VARCHAR(255) NOT NULL, language_code VARCHAR(16) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AUDIO_ASSET_ACTIVE_NAME ON audio_asset (active, name)');
        $this->addSql('CREATE TABLE flight_announcement_config (id UUID NOT NULL, flight_definition_id UUID NOT NULL, announcement_type VARCHAR(32) NOT NULL, enabled BOOLEAN NOT NULL, repeat_every_minutes INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FLIGHT_ANNOUNCEMENT_CONFIG_TYPE ON flight_announcement_config (flight_definition_id, announcement_type)');
        $this->addSql('CREATE INDEX IDX_FLIGHT_ANNOUNCEMENT_CONFIG_FLIGHT ON flight_announcement_config (flight_definition_id)');
        $this->addSql('CREATE TABLE announcement_variant (id UUID NOT NULL, config_id UUID NOT NULL, language_code VARCHAR(16) NOT NULL, sort_order INT NOT NULL, source_type VARCHAR(16) NOT NULL, audio_asset_id UUID DEFAULT NULL, text TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DE2E26724DB0683 ON announcement_variant (config_id)');
        $this->addSql('ALTER TABLE announcement_variant ADD CONSTRAINT FK_ANNOUNCEMENT_VARIANT_CONFIG FOREIGN KEY (config_id) REFERENCES flight_announcement_config (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement_variant DROP CONSTRAINT FK_ANNOUNCEMENT_VARIANT_CONFIG');
        $this->addSql('DROP TABLE announcement_variant');
        $this->addSql('DROP TABLE flight_announcement_config');
        $this->addSql('DROP TABLE audio_asset');
    }
}
