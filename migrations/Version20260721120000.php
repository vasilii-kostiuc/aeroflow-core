<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source and TTS cache attributes to audio assets (task 021).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE audio_asset ADD source VARCHAR(16) DEFAULT 'uploaded' NOT NULL");
        $this->addSql('ALTER TABLE audio_asset ADD tts_text_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE audio_asset ADD tts_voice VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE audio_asset ADD tts_model_version VARCHAR(128) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AUDIO_ASSET_TTS_CACHE ON audio_asset (source, active, tts_text_hash, language_code, tts_voice)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_AUDIO_ASSET_TTS_CACHE');
        $this->addSql('ALTER TABLE audio_asset DROP source');
        $this->addSql('ALTER TABLE audio_asset DROP tts_text_hash');
        $this->addSql('ALTER TABLE audio_asset DROP tts_voice');
        $this->addSql('ALTER TABLE audio_asset DROP tts_model_version');
    }
}
