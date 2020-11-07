<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201107144249 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE anatomy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE physical_qualities_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE workout_maximum_load_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE anatomy (id INT NOT NULL, height INT NOT NULL, weight DOUBLE PRECISION DEFAULT NULL, arm_span INT DEFAULT NULL, bust_length INT DEFAULT NULL, leg_length INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE physical_qualities (id INT NOT NULL, proprioception INT NOT NULL, weight_power_ratio INT NOT NULL, explosive_strength INT NOT NULL, endurance_strength INT NOT NULL, maximum_strength INT NOT NULL, stress_resistance INT NOT NULL, core_strength INT NOT NULL, flexibility INT NOT NULL, recovery INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE workout_maximum_load (id INT NOT NULL, rowing_tirage INT NOT NULL, bench_press INT NOT NULL, squat INT NOT NULL, leg_press INT NOT NULL, clean INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE app_user ADD anatomy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD physical_qualities_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD workout_maximum_load_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E95A5FF454 FOREIGN KEY (anatomy_id) REFERENCES anatomy (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E9D270B04E FOREIGN KEY (physical_qualities_id) REFERENCES physical_qualities (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E975B82207 FOREIGN KEY (workout_maximum_load_id) REFERENCES workout_maximum_load (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E95A5FF454 ON app_user (anatomy_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9D270B04E ON app_user (physical_qualities_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E975B82207 ON app_user (workout_maximum_load_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E95A5FF454');
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E9D270B04E');
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E975B82207');
        $this->addSql('DROP SEQUENCE anatomy_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE physical_qualities_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE workout_maximum_load_id_seq CASCADE');
        $this->addSql('DROP TABLE anatomy');
        $this->addSql('DROP TABLE physical_qualities');
        $this->addSql('DROP TABLE workout_maximum_load');
        $this->addSql('DROP INDEX UNIQ_88BDF3E95A5FF454');
        $this->addSql('DROP INDEX UNIQ_88BDF3E9D270B04E');
        $this->addSql('DROP INDEX UNIQ_88BDF3E975B82207');
        $this->addSql('ALTER TABLE app_user DROP anatomy_id');
        $this->addSql('ALTER TABLE app_user DROP physical_qualities_id');
        $this->addSql('ALTER TABLE app_user DROP workout_maximum_load_id');
    }
}
