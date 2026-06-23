<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add operational resource directories, audio prompts, composable announcement segments and prepared audio sequences.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE check_in_counter (id UUID NOT NULL, code VARCHAR(16) NOT NULL, display_name VARCHAR(128) NOT NULL, sort_order INT NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CHECK_IN_COUNTER_CODE ON check_in_counter (code)');
        $this->addSql('CREATE TABLE gate (id UUID NOT NULL, code VARCHAR(16) NOT NULL, display_name VARCHAR(128) NOT NULL, sort_order INT NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GATE_CODE ON gate (code)');
        $this->addSql('CREATE TABLE audio_prompt (id UUID NOT NULL, kind VARCHAR(32) NOT NULL, value VARCHAR(16) NOT NULL, language_code VARCHAR(16) NOT NULL, audio_asset_id UUID NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AUDIO_PROMPT_STATE ON audio_prompt (kind, value, language_code, active)');

        $this->addSql('CREATE TABLE announcement_template_segment (id UUID NOT NULL, variant_id UUID NOT NULL, sort_order INT NOT NULL, type VARCHAR(24) NOT NULL, audio_asset_id UUID DEFAULT NULL, slot VARCHAR(32) DEFAULT NULL, duration_ms INT DEFAULT NULL, text TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2B7FE0253B69A9AF ON announcement_template_segment (variant_id)');
        $this->addSql('ALTER TABLE announcement_template_segment ADD CONSTRAINT FK_ANNOUNCEMENT_SEGMENT_VARIANT FOREIGN KEY (variant_id) REFERENCES announcement_variant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql("INSERT INTO announcement_template_segment (id, variant_id, sort_order, type, audio_asset_id, slot, duration_ms, text, created_at, updated_at) SELECT gen_random_uuid(), id, 1, CASE WHEN source_type = 'audio_asset' THEN 'audio_asset' ELSE 'text' END, audio_asset_id, NULL, NULL, text, created_at, updated_at FROM announcement_variant");
        $this->addSql('ALTER TABLE announcement_variant DROP source_type');
        $this->addSql('ALTER TABLE announcement_variant DROP audio_asset_id');
        $this->addSql('ALTER TABLE announcement_variant DROP text');

        $this->addSql("ALTER TABLE announcement ADD check_in_counters JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE announcement ADD gate JSON DEFAULT NULL');
        $this->addSql("ALTER TABLE announcement ADD audio_sequence JSON NOT NULL DEFAULT '[]'");
        $this->addSql("UPDATE announcement SET check_in_counters = CASE WHEN check_in_counter_start IS NULL THEN '[]'::json ELSE json_build_array(json_build_object('id', NULL, 'code', CASE WHEN check_in_counter_start = check_in_counter_end THEN check_in_counter_start::text ELSE check_in_counter_start::text || '-' || check_in_counter_end::text END)) END");
        $this->addSql("UPDATE announcement SET gate = CASE WHEN gate_code IS NULL THEN NULL ELSE json_build_object('id', NULL, 'code', gate_code) END");
        $this->addSql('ALTER TABLE announcement DROP check_in_counter_start');
        $this->addSql('ALTER TABLE announcement DROP check_in_counter_end');
        $this->addSql('ALTER TABLE announcement DROP gate_code');
        $this->addSql('ALTER TABLE announcement ALTER check_in_counters DROP DEFAULT');
        $this->addSql('ALTER TABLE announcement ALTER audio_sequence DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            (bool) $this->connection->fetchOne('SELECT EXISTS (SELECT 1 FROM announcement_variant v WHERE (SELECT COUNT(*) FROM announcement_template_segment s WHERE s.variant_id = v.id) <> 1)'),
            'Cannot downgrade: every announcement variant must contain exactly one segment.',
        );
        $this->abortIf(
            (bool) $this->connection->fetchOne("SELECT EXISTS (SELECT 1 FROM announcement_template_segment WHERE type NOT IN ('audio_asset', 'text'))"),
            'Cannot downgrade: pause and dynamic-slot segments cannot be represented by the legacy schema.',
        );

        $this->addSql('ALTER TABLE announcement_variant ADD source_type VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE announcement_variant ADD audio_asset_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE announcement_variant ADD text TEXT DEFAULT NULL');
        $this->addSql('UPDATE announcement_variant v SET source_type = s.type, audio_asset_id = s.audio_asset_id, text = s.text FROM announcement_template_segment s WHERE s.variant_id = v.id');
        $this->addSql('ALTER TABLE announcement_variant ALTER source_type SET NOT NULL');

        $this->addSql('ALTER TABLE announcement ADD check_in_counter_start INT DEFAULT NULL');
        $this->addSql('ALTER TABLE announcement ADD check_in_counter_end INT DEFAULT NULL');
        $this->addSql('ALTER TABLE announcement ADD gate_code VARCHAR(16) DEFAULT NULL');
        $this->addSql("UPDATE announcement SET gate_code = gate->>'code'");
        $this->addSql('ALTER TABLE announcement DROP check_in_counters');
        $this->addSql('ALTER TABLE announcement DROP gate');
        $this->addSql('ALTER TABLE announcement DROP audio_sequence');

        $this->addSql('ALTER TABLE announcement_template_segment DROP CONSTRAINT FK_ANNOUNCEMENT_SEGMENT_VARIANT');
        $this->addSql('DROP TABLE announcement_template_segment');
        $this->addSql('DROP TABLE audio_prompt');
        $this->addSql('DROP TABLE gate');
        $this->addSql('DROP TABLE check_in_counter');
    }
}
