<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Task 017: failed playback events carry a reason; the dispatcher's queue screen
 * shows it in the recent section.
 */
final class Version20260710160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reason to playback_event_receipt.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE playback_event_receipt ADD reason VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE playback_event_receipt DROP reason');
    }
}
