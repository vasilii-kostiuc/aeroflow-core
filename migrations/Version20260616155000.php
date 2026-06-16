<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616155000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh token storage for user access.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_refresh_token (id UUID NOT NULL, user_id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, replaced_by_hash VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REFRESH_TOKEN_HASH ON user_refresh_token (token_hash)');
        $this->addSql('CREATE INDEX IDX_REFRESH_TOKEN_USER ON user_refresh_token (user_id)');
        $this->addSql('ALTER TABLE user_refresh_token ADD CONSTRAINT FK_REFRESH_TOKEN_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_refresh_token DROP CONSTRAINT FK_REFRESH_TOKEN_USER');
        $this->addSql('DROP TABLE user_refresh_token');
    }
}
