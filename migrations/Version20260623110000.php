<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uploaded file metadata to audio assets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audio_asset ADD storage_key VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE audio_asset ADD mime_type VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE audio_asset ADD size_bytes INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audio_asset DROP storage_key');
        $this->addSql('ALTER TABLE audio_asset DROP mime_type');
        $this->addSql('ALTER TABLE audio_asset DROP size_bytes');
    }
}
