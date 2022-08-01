<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220328101447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE training_phase_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE training_phase (id INT NOT NULL, training_id INT NOT NULL, duration INT NOT NULL, distance INT NOT NULL, times integer[] NOT NULL, distances integer[] NOT NULL, paces integer[] NOT NULL, stroke_rates integer[] NOT NULL, heart_rates integer[] DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5E675CA6BEFD98D1 ON training_phase (training_id)');
        $this->addSql('ALTER TABLE training_phase ADD CONSTRAINT FK_5E675CA6BEFD98D1 FOREIGN KEY (training_id) REFERENCES training (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ADD concept2_refresh_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD concept2_last_import_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN app_user.concept2_last_import_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE training_phase_id_seq CASCADE');
        $this->addSql('DROP TABLE training_phase');
        $this->addSql('ALTER TABLE app_user DROP concept2_refresh_token');
        $this->addSql('ALTER TABLE app_user DROP concept2_last_import_at');
    }
}
