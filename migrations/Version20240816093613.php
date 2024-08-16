<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240816093613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE planned_training_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE training_plan_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE planned_training (id INT NOT NULL, training_plan_id INT NOT NULL, date DATE NOT NULL, day_part VARCHAR(255) NOT NULL, duration INT NOT NULL, distance INT DEFAULT NULL, sport VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, rated_perceived_exertion INT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F4E656DD35A79295 ON planned_training (training_plan_id)');
        $this->addSql('COMMENT ON COLUMN planned_training.date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE planned_training_user (planned_training_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(planned_training_id, user_id))');
        $this->addSql('CREATE INDEX IDX_920AC56F4698DE38 ON planned_training_user (planned_training_id)');
        $this->addSql('CREATE INDEX IDX_920AC56FA76ED395 ON planned_training_user (user_id)');
        $this->addSql('CREATE TABLE training_plan (id INT NOT NULL, name VARCHAR(255) NOT NULL, start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN training_plan.start_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN training_plan.end_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE planned_training ADD CONSTRAINT FK_F4E656DD35A79295 FOREIGN KEY (training_plan_id) REFERENCES training_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE planned_training_user ADD CONSTRAINT FK_920AC56F4698DE38 FOREIGN KEY (planned_training_id) REFERENCES planned_training (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE planned_training_user ADD CONSTRAINT FK_920AC56FA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ADD training_plan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E935A79295 FOREIGN KEY (training_plan_id) REFERENCES training_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_88BDF3E935A79295 ON app_user (training_plan_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E935A79295');
        $this->addSql('DROP SEQUENCE planned_training_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE training_plan_id_seq CASCADE');
        $this->addSql('ALTER TABLE planned_training DROP CONSTRAINT FK_F4E656DD35A79295');
        $this->addSql('ALTER TABLE planned_training_user DROP CONSTRAINT FK_920AC56F4698DE38');
        $this->addSql('ALTER TABLE planned_training_user DROP CONSTRAINT FK_920AC56FA76ED395');
        $this->addSql('DROP TABLE planned_training');
        $this->addSql('DROP TABLE planned_training_user');
        $this->addSql('DROP TABLE training_plan');
        $this->addSql('DROP INDEX IDX_88BDF3E935A79295');
        $this->addSql('ALTER TABLE app_user DROP training_plan_id');
    }
}
