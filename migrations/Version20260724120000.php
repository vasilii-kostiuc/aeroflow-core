<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow announcements without a flight for airport-wide supplementary announcements (task 024).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement ALTER flight_definition_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcement ALTER flight_definition_id SET NOT NULL');
    }
}
