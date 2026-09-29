<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929162147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE training_split_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE training_split (id INT NOT NULL, duration INT NOT NULL, distance INT NOT NULL, stroke_rate INT DEFAULT NULL, average_heart_rate INT DEFAULT NULL, ending_heart_rate INT DEFAULT NULL, training_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5E2F107CBEFD98D1 ON training_split (training_id)');
        $this->addSql('ALTER TABLE training_split ADD CONSTRAINT FK_5E2F107CBEFD98D1 FOREIGN KEY (training_id) REFERENCES training (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE training_phase ADD rest_duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE training_phase ADD rest_distance INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE training_split_id_seq CASCADE');
        $this->addSql('ALTER TABLE training_split DROP CONSTRAINT FK_5E2F107CBEFD98D1');
        $this->addSql('DROP TABLE training_split');
        $this->addSql('ALTER TABLE training_phase DROP rest_duration');
        $this->addSql('ALTER TABLE training_phase DROP rest_distance');
    }
}
