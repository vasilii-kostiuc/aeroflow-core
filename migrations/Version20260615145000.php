<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615145000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change user id column to PostgreSQL UUID type.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER id TYPE UUID USING id::uuid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER id TYPE VARCHAR(255)');
    }
}
