<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200501204048 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE season_user_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE license_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE license (id INT NOT NULL, season_id INT NOT NULL, app_user INT NOT NULL, medical_certificate_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5768F4194EC001D1 ON license (season_id)');
        $this->addSql('CREATE INDEX IDX_5768F41988BDF3E9 ON license (app_user)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5768F419D7FB9428 ON license (medical_certificate_id)');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F4194EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F41988BDF3E9 FOREIGN KEY (app_user) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F419D7FB9428 FOREIGN KEY (medical_certificate_id) REFERENCES medical_certificate (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE season_user');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE license_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE season_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE season_user (id INT NOT NULL, season_id INT NOT NULL, app_user INT NOT NULL, medical_certificate_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_bda4ad7d7fb9428 ON season_user (medical_certificate_id)');
        $this->addSql('CREATE INDEX idx_bda4ad788bdf3e9 ON season_user (app_user)');
        $this->addSql('CREATE INDEX idx_bda4ad74ec001d1 ON season_user (season_id)');
        $this->addSql('ALTER TABLE season_user ADD CONSTRAINT fk_bda4ad74ec001d1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE season_user ADD CONSTRAINT fk_bda4ad788bdf3e9 FOREIGN KEY (app_user) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE season_user ADD CONSTRAINT fk_bda4ad7d7fb9428 FOREIGN KEY (medical_certificate_id) REFERENCES medical_certificate (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE license');
    }
}
