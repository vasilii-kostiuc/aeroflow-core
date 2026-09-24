<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20260924120000 extends AbstractMigration
{
    /**
     * Top level of the legacy SAO library. Ordinary folders: admins may rename,
     * move or delete them.
     */
    private const array STARTER_FOLDERS = ['Вылеты', 'Прибытие', 'Дополнительно', 'Экстренные', 'Числа', 'Гонги', 'Музыка'];

    public function getDescription(): string
    {
        return 'Audio library folders, asset titles and asset folder placement (task 031).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audio_folder (id UUID NOT NULL, name VARCHAR(100) NOT NULL, name_key VARCHAR(100) NOT NULL, parent_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_AUDIO_FOLDER_PARENT ON audio_folder (parent_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AUDIO_FOLDER_CHILD_NAME ON audio_folder (parent_id, name_key) WHERE parent_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AUDIO_FOLDER_ROOT_NAME ON audio_folder (name_key) WHERE parent_id IS NULL');

        foreach (self::STARTER_FOLDERS as $name) {
            $this->addSql(
                'INSERT INTO audio_folder (id, name, name_key, parent_id, created_at, updated_at) VALUES (:id, :name, :key, NULL, NOW(), NOW())',
                ['id' => Uuid::v7()->toRfc4122(), 'name' => $name, 'key' => mb_strtolower($name)],
            );
        }

        // Same default as AudioAsset::titleFromFileName(): the file name without
        // its extension, runs of whitespace, `-` and `_` collapsed into a space.
        $this->addSql('ALTER TABLE audio_asset ADD title VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE audio_asset SET title = COALESCE(NULLIF(BTRIM(REGEXP_REPLACE(REGEXP_REPLACE(name, '\\.[^./]*$', ''), '[[:space:]_-]+', ' ', 'g')), ''), name)");
        $this->addSql('ALTER TABLE audio_asset ALTER title SET NOT NULL');

        $this->addSql('ALTER TABLE audio_asset ADD folder_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AUDIO_ASSET_FOLDER ON audio_asset (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_AUDIO_ASSET_FOLDER');
        $this->addSql('ALTER TABLE audio_asset DROP folder_id');
        $this->addSql('ALTER TABLE audio_asset DROP title');
        $this->addSql('DROP TABLE audio_folder');
    }
}
