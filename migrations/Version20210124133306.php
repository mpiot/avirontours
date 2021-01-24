<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210124133306 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE training_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE training (id INT NOT NULL, user_id INT NOT NULL, trained_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, duration TIME(0) WITHOUT TIME ZONE NOT NULL, distance DOUBLE PRECISION DEFAULT NULL, sport VARCHAR(255) NOT NULL, feeling DOUBLE PRECISION DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D5128A8FA76ED395 ON training (user_id)');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8FA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE training_id_seq CASCADE');
        $this->addSql('DROP TABLE training');
    }
}
