<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231222130524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE uploaded_file_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE uploaded_file (id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, visibility VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B40DF75DB03A8386 ON uploaded_file (created_by_id)');
        $this->addSql('CREATE INDEX IDX_B40DF75D896DBBDE ON uploaded_file (updated_by_id)');
        $this->addSql('COMMENT ON COLUMN uploaded_file.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN uploaded_file.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE uploaded_file ADD CONSTRAINT FK_B40DF75DB03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE uploaded_file ADD CONSTRAINT FK_B40DF75D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE license ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE license ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN license.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN license.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE medical_certificate ADD uploaded_file_id INT DEFAULT NULL');
        $this->addSql('
            DO $$
            DECLARE
                medicalCertificate record;
            BEGIN
            FOR medicalCertificate IN
                SELECT * FROM medical_certificate
            LOOP
                INSERT INTO uploaded_file (id, created_by_id, updated_by_id, filename, original_filename, mime_type, visibility, created_at, updated_at)
                VALUES (
                    NEXTVAL(\'uploaded_file_id_seq\'),
                    null,
                    null,
                    medicalCertificate.file_name,
                    medicalCertificate.file_name,
                    medicalCertificate.file_mime_type,
                    \'private\',
                    medicalCertificate.date,
                    medicalCertificate.date
                );

                UPDATE medical_certificate
                SET uploaded_file_id = (SELECT CURRVAL(\'uploaded_file_id_seq\'))
                WHERE id = medicalCertificate.id;
            END LOOP;
            END$$;
        ');
        $this->addSql('ALTER TABLE medical_certificate ALTER COLUMN uploaded_file_id SET NOT NULL');
        $this->addSql('ALTER TABLE medical_certificate DROP file_name');
        $this->addSql('ALTER TABLE medical_certificate DROP file_size');
        $this->addSql('ALTER TABLE medical_certificate DROP updated_at');
        $this->addSql('ALTER TABLE medical_certificate DROP file_mime_type');
        $this->addSql('ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F8276973A0 FOREIGN KEY (uploaded_file_id) REFERENCES uploaded_file (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B36515F8276973A0 ON medical_certificate (uploaded_file_id)');
        $this->addSql('ALTER TABLE shell_damage ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE shell_damage ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN shell_damage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN shell_damage.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE medical_certificate DROP CONSTRAINT FK_B36515F8276973A0');
        $this->addSql('DROP SEQUENCE uploaded_file_id_seq CASCADE');
        $this->addSql('ALTER TABLE uploaded_file DROP CONSTRAINT FK_B40DF75DB03A8386');
        $this->addSql('ALTER TABLE uploaded_file DROP CONSTRAINT FK_B40DF75D896DBBDE');
        $this->addSql('DROP TABLE uploaded_file');
        $this->addSql('ALTER TABLE shell_damage ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE shell_damage ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN shell_damage.created_at IS NULL');
        $this->addSql('COMMENT ON COLUMN shell_damage.updated_at IS NULL');
        $this->addSql('ALTER TABLE license ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE license ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN license.created_at IS NULL');
        $this->addSql('COMMENT ON COLUMN license.updated_at IS NULL');
        $this->addSql('DROP INDEX UNIQ_B36515F8276973A0');
        $this->addSql('ALTER TABLE medical_certificate ADD file_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_certificate ADD file_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_certificate ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_certificate ADD file_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE medical_certificate DROP uploaded_file_id');
    }
}
