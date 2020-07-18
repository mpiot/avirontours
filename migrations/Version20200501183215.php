<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200501183215 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE season_category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE season_category (id INT NOT NULL, season_id INT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, license_type VARCHAR(1) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8ABA1C9B4EC001D1 ON season_category (season_id)');
        $this->addSql('ALTER TABLE season_category ADD CONSTRAINT FK_8ABA1C9B4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE season ADD subscription_enabled BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE season ADD active BOOLEAN DEFAULT NULL');
        $this->addSql('UPDATE season SET subscription_enabled = true, active = true');
        $this->addSql('ALTER TABLE season ALTER COLUMN subscription_enabled SET NOT NULL, ALTER COLUMN active SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE season_category_id_seq CASCADE');
        $this->addSql('DROP TABLE season_category');
        $this->addSql('ALTER TABLE season DROP subscription_enabled');
        $this->addSql('ALTER TABLE season DROP active');
    }
}
