<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Task 015: records inbound playback integration events (queued/started/completed).
 * Unique message_id keeps reception idempotent; announcement_id is indexed to read
 * an announcement's playback timeline.
 */
final class Version20260709121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create playback_event_receipt table for inbound playback events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE playback_event_receipt (id UUID NOT NULL, message_id UUID NOT NULL, event VARCHAR(64) NOT NULL, announcement_id UUID NOT NULL, job_id UUID NOT NULL, occurred_at VARCHAR(64) NOT NULL, received_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PLAYBACK_EVENT_RECEIPT_MESSAGE ON playback_event_receipt (message_id)');
        $this->addSql('CREATE INDEX IDX_PLAYBACK_EVENT_RECEIPT_ANNOUNCEMENT ON playback_event_receipt (announcement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE playback_event_receipt');
    }
}
