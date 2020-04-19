<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200419141440 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT fk_f11d61a27597d3fe');
        $this->addSql('ALTER TABLE logbook_entry_member DROP CONSTRAINT fk_2ca459e7597d3fe');
        $this->addSql('ALTER TABLE medical_certificate DROP CONSTRAINT fk_b36515f87597d3fe');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT fk_70e4fa78f5b7af75');
        $this->addSql('DROP SEQUENCE invitation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE address_id_seq CASCADE');
        $this->addSql('CREATE TABLE logbook_entry_user (logbook_entry_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(logbook_entry_id, user_id))');
        $this->addSql('CREATE INDEX IDX_8C39883021C3AF14 ON logbook_entry_user (logbook_entry_id)');
        $this->addSql('CREATE INDEX IDX_8C398830A76ED395 ON logbook_entry_user (user_id)');
        $this->addSql('ALTER TABLE logbook_entry_user ADD CONSTRAINT FK_8C39883021C3AF14 FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logbook_entry_user ADD CONSTRAINT FK_8C398830A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE invitation');
        $this->addSql('DROP TABLE logbook_entry_member');
        $this->addSql('DROP TABLE member');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP INDEX idx_b36515f87597d3fe');
        $this->addSql('ALTER TABLE medical_certificate RENAME COLUMN member_id TO user_id');
        $this->addSql('ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F8A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B36515F8A76ED395 ON medical_certificate (user_id)');
        $this->addSql('DROP INDEX uniq_88bdf3e9e7927c74');
        $this->addSql('ALTER TABLE app_user ADD username VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD gender VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD first_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD last_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD license_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD license_end_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD license_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD rower_category INT NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD birthday DATE NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD legal_representative VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD subscription_date DATE NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD lane_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD lane_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD lane_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD postal_code INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD city VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD phone_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ALTER password DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9F85E0677 ON app_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9EC7E7152 ON app_user (license_number)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE invitation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE address_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE invitation (id INT NOT NULL, member_id INT NOT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_f11d61a27597d3fe ON invitation (member_id)');
        $this->addSql('CREATE TABLE logbook_entry_member (logbook_entry_id INT NOT NULL, member_id INT NOT NULL, PRIMARY KEY(logbook_entry_id, member_id))');
        $this->addSql('CREATE INDEX idx_2ca459e21c3af14 ON logbook_entry_member (logbook_entry_id)');
        $this->addSql('CREATE INDEX idx_2ca459e7597d3fe ON logbook_entry_member (member_id)');
        $this->addSql('CREATE TABLE member (id INT NOT NULL, user_id INT DEFAULT NULL, address_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, license_number VARCHAR(255) DEFAULT NULL, license_end_at DATE DEFAULT NULL, rower_category INT NOT NULL, gender VARCHAR(255) NOT NULL, license_type VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, birthday DATE NOT NULL, legal_representative VARCHAR(255) DEFAULT NULL, subscription_date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_70e4fa78a76ed395 ON member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_70e4fa78f5b7af75 ON member (address_id)');
        $this->addSql('CREATE TABLE address (id INT NOT NULL, number VARCHAR(255) DEFAULT NULL, lane_type VARCHAR(255) DEFAULT NULL, lane_name VARCHAR(255) DEFAULT NULL, postal_code INT DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT fk_f11d61a27597d3fe FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logbook_entry_member ADD CONSTRAINT fk_2ca459e21c3af14 FOREIGN KEY (logbook_entry_id) REFERENCES logbook_entry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE logbook_entry_member ADD CONSTRAINT fk_2ca459e7597d3fe FOREIGN KEY (member_id) REFERENCES member (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT fk_70e4fa78a76ed395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT fk_70e4fa78f5b7af75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE logbook_entry_user');
        $this->addSql('DROP INDEX UNIQ_88BDF3E9F85E0677');
        $this->addSql('DROP INDEX UNIQ_88BDF3E9EC7E7152');
        $this->addSql('ALTER TABLE app_user DROP username');
        $this->addSql('ALTER TABLE app_user DROP gender');
        $this->addSql('ALTER TABLE app_user DROP first_name');
        $this->addSql('ALTER TABLE app_user DROP last_name');
        $this->addSql('ALTER TABLE app_user DROP license_number');
        $this->addSql('ALTER TABLE app_user DROP license_end_at');
        $this->addSql('ALTER TABLE app_user DROP license_type');
        $this->addSql('ALTER TABLE app_user DROP rower_category');
        $this->addSql('ALTER TABLE app_user DROP birthday');
        $this->addSql('ALTER TABLE app_user DROP legal_representative');
        $this->addSql('ALTER TABLE app_user DROP subscription_date');
        $this->addSql('ALTER TABLE app_user DROP lane_number');
        $this->addSql('ALTER TABLE app_user DROP lane_type');
        $this->addSql('ALTER TABLE app_user DROP lane_name');
        $this->addSql('ALTER TABLE app_user DROP postal_code');
        $this->addSql('ALTER TABLE app_user DROP city');
        $this->addSql('ALTER TABLE app_user DROP phone_number');
        $this->addSql('ALTER TABLE app_user ALTER password SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_88bdf3e9e7927c74 ON app_user (email)');
        $this->addSql('ALTER TABLE medical_certificate DROP CONSTRAINT FK_B36515F8A76ED395');
        $this->addSql('DROP INDEX IDX_B36515F8A76ED395');
        $this->addSql('ALTER TABLE medical_certificate RENAME COLUMN user_id TO member_id');
        $this->addSql('ALTER TABLE medical_certificate ADD CONSTRAINT fk_b36515f87597d3fe FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b36515f87597d3fe ON medical_certificate (member_id)');
    }
}
