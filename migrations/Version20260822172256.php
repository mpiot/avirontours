<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260822172256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT fk_88bdf3e9d270b04e');
        $this->addSql('DROP INDEX uniq_88bdf3e9d270b04e');
        $this->addSql('ALTER TABLE app_user DROP physical_qualities_id');
        $this->addSql('DROP SEQUENCE physical_qualities_id_seq CASCADE');
        $this->addSql('DROP TABLE physical_qualities');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE physical_qualities_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE physical_qualities (id INT NOT NULL, proprioception INT NOT NULL, weight_power_ratio INT NOT NULL, explosive_strength INT NOT NULL, endurance_strength INT NOT NULL, maximum_strength INT NOT NULL, stress_resistance INT NOT NULL, core_strength INT NOT NULL, flexibility INT NOT NULL, recovery INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE app_user ADD physical_qualities_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT fk_88bdf3e9d270b04e FOREIGN KEY (physical_qualities_id) REFERENCES physical_qualities (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_88bdf3e9d270b04e ON app_user (physical_qualities_id)');
    }
}
