<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210904150756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE training_phase_id_seq CASCADE');
        $this->addSql('DROP TABLE training_phase');
        $this->addSql('ALTER TABLE training ADD energy_pathway VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE training DROP duration');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE training_phase_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE training_phase (id INT NOT NULL, training_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, intensity INT NOT NULL, duration VARCHAR(255) NOT NULL, distance DOUBLE PRECISION DEFAULT NULL, split VARCHAR(6) DEFAULT NULL, spm INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_5e675ca6befd98d1 ON training_phase (training_id)');
        $this->addSql('COMMENT ON COLUMN training_phase.duration IS \'(DC2Type:dateinterval)\'');
        $this->addSql('ALTER TABLE training_phase ADD CONSTRAINT fk_5e675ca6befd98d1 FOREIGN KEY (training_id) REFERENCES training (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE training ADD duration VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE training DROP energy_pathway');
        $this->addSql('COMMENT ON COLUMN training.duration IS \'(DC2Type:dateinterval)\'');
    }
}
