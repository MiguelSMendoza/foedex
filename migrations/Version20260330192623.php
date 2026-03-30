<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330192623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, display_name VARCHAR(120) NOT NULL, bio LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT NOT NULL, UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), INDEX IDX_64C19C1B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, current_slug VARCHAR(160) NOT NULL, current_title VARCHAR(160) NOT NULL, current_excerpt LONGTEXT DEFAULT NULL, current_markdown LONGTEXT NOT NULL, current_html LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_archived TINYINT NOT NULL, created_by_id INT NOT NULL, last_edited_by_id INT NOT NULL, UNIQUE INDEX UNIQ_140AB620572EF204 (current_slug), INDEX IDX_140AB620B03A8386 (created_by_id), INDEX IDX_140AB620D48D54E8 (last_edited_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_category (page_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_86D31EE1C4663E4 (page_id), INDEX IDX_86D31EE112469DE2 (category_id), PRIMARY KEY (page_id, category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_revision (id INT AUTO_INCREMENT NOT NULL, revision_number INT NOT NULL, title_snapshot VARCHAR(160) NOT NULL, excerpt_snapshot LONGTEXT DEFAULT NULL, markdown_snapshot LONGTEXT NOT NULL, html_snapshot LONGTEXT NOT NULL, change_summary VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, page_id INT NOT NULL, author_id INT NOT NULL, restored_from_revision_id INT DEFAULT NULL, INDEX IDX_EDFC12ECC4663E4 (page_id), INDEX IDX_EDFC12ECF675F31B (author_id), INDEX IDX_EDFC12ECAD65EC32 (restored_from_revision_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_revision_category (page_revision_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_C31440F9A4622FD (page_revision_id), INDEX IDX_C31440F912469DE2 (category_id), PRIMARY KEY (page_revision_id, category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_slug_redirect (id INT AUTO_INCREMENT NOT NULL, old_slug VARCHAR(160) NOT NULL, created_at DATETIME NOT NULL, page_id INT NOT NULL, created_by_id INT NOT NULL, UNIQUE INDEX UNIQ_3432927B0001AC7 (old_slug), INDEX IDX_3432927C4663E4 (page_id), INDEX IDX_3432927B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620B03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620D48D54E8 FOREIGN KEY (last_edited_by_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE page_category ADD CONSTRAINT FK_86D31EE1C4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_category ADD CONSTRAINT FK_86D31EE112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_revision ADD CONSTRAINT FK_EDFC12ECC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE page_revision ADD CONSTRAINT FK_EDFC12ECF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE page_revision ADD CONSTRAINT FK_EDFC12ECAD65EC32 FOREIGN KEY (restored_from_revision_id) REFERENCES page_revision (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE page_revision_category ADD CONSTRAINT FK_C31440F9A4622FD FOREIGN KEY (page_revision_id) REFERENCES page_revision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_revision_category ADD CONSTRAINT FK_C31440F912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_slug_redirect ADD CONSTRAINT FK_3432927C4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_slug_redirect ADD CONSTRAINT FK_3432927B03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620B03A8386');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620D48D54E8');
        $this->addSql('ALTER TABLE page_category DROP FOREIGN KEY FK_86D31EE1C4663E4');
        $this->addSql('ALTER TABLE page_category DROP FOREIGN KEY FK_86D31EE112469DE2');
        $this->addSql('ALTER TABLE page_revision DROP FOREIGN KEY FK_EDFC12ECC4663E4');
        $this->addSql('ALTER TABLE page_revision DROP FOREIGN KEY FK_EDFC12ECF675F31B');
        $this->addSql('ALTER TABLE page_revision DROP FOREIGN KEY FK_EDFC12ECAD65EC32');
        $this->addSql('ALTER TABLE page_revision_category DROP FOREIGN KEY FK_C31440F9A4622FD');
        $this->addSql('ALTER TABLE page_revision_category DROP FOREIGN KEY FK_C31440F912469DE2');
        $this->addSql('ALTER TABLE page_slug_redirect DROP FOREIGN KEY FK_3432927C4663E4');
        $this->addSql('ALTER TABLE page_slug_redirect DROP FOREIGN KEY FK_3432927B03A8386');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE page_category');
        $this->addSql('DROP TABLE page_revision');
        $this->addSql('DROP TABLE page_revision_category');
        $this->addSql('DROP TABLE page_slug_redirect');
    }
}
