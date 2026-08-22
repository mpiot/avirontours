<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260822171324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT fk_88bdf3e95a5ff454');
        $this->addSql('DROP INDEX uniq_88bdf3e95a5ff454');
        $this->addSql('ALTER TABLE app_user DROP anatomy_id');
        $this->addSql('DROP SEQUENCE anatomy_id_seq CASCADE');
        $this->addSql('DROP TABLE anatomy');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE anatomy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE anatomy (id INT NOT NULL, height INT NOT NULL, weight DOUBLE PRECISION DEFAULT NULL, arm_span INT DEFAULT NULL, bust_length INT DEFAULT NULL, leg_length INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE app_user ADD anatomy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT fk_88bdf3e95a5ff454 FOREIGN KEY (anatomy_id) REFERENCES anatomy (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_88bdf3e95a5ff454 ON app_user (anatomy_id)');
    }
}
