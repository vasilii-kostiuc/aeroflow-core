<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant ROLE_ADMIN to every existing user (task 033): before roles everyone had full access, so nobody loses it and the system keeps an administrator.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE "user" SET roles = \'["ROLE_ADMIN"]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE "user" SET roles = \'[]\'');
    }
}
