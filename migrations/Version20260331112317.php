<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331112317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media_asset (id INT AUTO_INCREMENT NOT NULL, kind VARCHAR(20) NOT NULL, original_filename VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) NOT NULL, public_path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(120) NOT NULL, size INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, created_at DATETIME NOT NULL, page_id INT NOT NULL, created_by_id INT NOT NULL, UNIQUE INDEX UNIQ_1DB69EEDDF8EB9B7 (stored_filename), INDEX IDX_1DB69EEDC4663E4 (page_id), INDEX IDX_1DB69EEDB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE media_asset ADD CONSTRAINT FK_1DB69EEDC4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_asset ADD CONSTRAINT FK_1DB69EEDB03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_asset DROP FOREIGN KEY FK_1DB69EEDC4663E4');
        $this->addSql('ALTER TABLE media_asset DROP FOREIGN KEY FK_1DB69EEDB03A8386');
        $this->addSql('DROP TABLE media_asset');
    }
}
