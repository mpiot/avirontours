<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200421165031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE season_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE season_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE season (id INT NOT NULL, name INT NOT NULL, license_end_at DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE season_user (id INT NOT NULL, season_id INT NOT NULL, app_user INT NOT NULL, medical_certificate_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BDA4AD74EC001D1 ON season_user (season_id)');
        $this->addSql('CREATE INDEX IDX_BDA4AD788BDF3E9 ON season_user (app_user)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BDA4AD7D7FB9428 ON season_user (medical_certificate_id)');
        $this->addSql('ALTER TABLE season_user ADD CONSTRAINT FK_BDA4AD74EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE season_user ADD CONSTRAINT FK_BDA4AD788BDF3E9 FOREIGN KEY (app_user) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE season_user ADD CONSTRAINT FK_BDA4AD7D7FB9428 FOREIGN KEY (medical_certificate_id) REFERENCES medical_certificate (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ALTER roles DROP NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE season_user DROP CONSTRAINT FK_BDA4AD74EC001D1');
        $this->addSql('DROP SEQUENCE season_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE season_user_id_seq CASCADE');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE season_user');
        $this->addSql('ALTER TABLE app_user ALTER roles SET NOT NULL');
    }
}
