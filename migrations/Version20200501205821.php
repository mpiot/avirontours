<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200501205821 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license DROP CONSTRAINT fk_5768f4194ec001d1');
        $this->addSql('DROP INDEX idx_5768f4194ec001d1');
        $this->addSql('ALTER TABLE license ADD license_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE license RENAME COLUMN season_id TO season_category_id');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F4195AC49564 FOREIGN KEY (season_category_id) REFERENCES season_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5768F4195AC49564 ON license (season_category_id)');
        $this->addSql('DROP INDEX uniq_88bdf3e9ec7e7152');
        $this->addSql('ALTER TABLE app_user DROP license_number');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license DROP CONSTRAINT FK_5768F4195AC49564');
        $this->addSql('DROP INDEX IDX_5768F4195AC49564');
        $this->addSql('ALTER TABLE license DROP license_number');
        $this->addSql('ALTER TABLE license RENAME COLUMN season_category_id TO season_id');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT fk_5768f4194ec001d1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_5768f4194ec001d1 ON license (season_id)');
        $this->addSql('ALTER TABLE app_user ADD license_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_88bdf3e9ec7e7152 ON app_user (license_number)');
    }
}
