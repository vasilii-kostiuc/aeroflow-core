<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record the next repeat tick carried by rescheduled playback events (task 023).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE playback_event_receipt ADD next_at VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE playback_event_receipt DROP next_at');
    }
}
