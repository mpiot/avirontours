<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200423170207 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE medical_certificate DROP CONSTRAINT fk_b36515f8a76ed395');
        $this->addSql('DROP INDEX idx_b36515f8a76ed395');
        $this->addSql('ALTER TABLE medical_certificate DROP user_id');
        $this->addSql('ALTER TABLE season_user ALTER season_id DROP DEFAULT');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE medical_certificate ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE medical_certificate ADD CONSTRAINT fk_b36515f8a76ed395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b36515f8a76ed395 ON medical_certificate (user_id)');
        $this->addSql('ALTER TABLE season_user ALTER season_id SET DEFAULT 1');
    }
}
