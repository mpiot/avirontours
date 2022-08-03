<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220803093503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE legal_guardian_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE legal_guardian (id INT NOT NULL, role VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE app_user ADD first_legal_guardian_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD second_legal_guardian_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E9E68F2E8A FOREIGN KEY (first_legal_guardian_id) REFERENCES legal_guardian (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E98F87CEB4 FOREIGN KEY (second_legal_guardian_id) REFERENCES legal_guardian (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E68F2E8A ON app_user (first_legal_guardian_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E98F87CEB4 ON app_user (second_legal_guardian_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E9E68F2E8A');
        $this->addSql('ALTER TABLE app_user DROP CONSTRAINT FK_88BDF3E98F87CEB4');
        $this->addSql('DROP SEQUENCE legal_guardian_id_seq CASCADE');
        $this->addSql('DROP TABLE legal_guardian');
        $this->addSql('DROP INDEX UNIQ_88BDF3E9E68F2E8A');
        $this->addSql('DROP INDEX UNIQ_88BDF3E98F87CEB4');
        $this->addSql('ALTER TABLE app_user DROP first_legal_guardian_id');
        $this->addSql('ALTER TABLE app_user DROP second_legal_guardian_id');
    }
}
