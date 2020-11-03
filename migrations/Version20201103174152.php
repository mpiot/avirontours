<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201103174152 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE physiology_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE physiology (id INT NOT NULL, light_aerobic_heart_rate INT DEFAULT NULL, heavy_aerobic_heart_rate INT DEFAULT NULL, anaerobic_threshold_heart_rate INT DEFAULT NULL, oxygen_transportation_heart_rate INT DEFAULT NULL, anaerobic_heart_rate INT DEFAULT NULL, maximum_heart_rate INT DEFAULT NULL, maximum_oxygen_consumption DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE app_user ADD physiology_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E9EB553482 FOREIGN KEY (physiology_id) REFERENCES physiology (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9EB553482 ON app_user (physiology_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E9EB553482');
        $this->addSql('DROP SEQUENCE physiology_id_seq CASCADE');
        $this->addSql('DROP TABLE physiology');
        $this->addSql('DROP INDEX UNIQ_88BDF3E9EB553482');
        $this->addSql('ALTER TABLE app_user DROP physiology_id');
    }
}
